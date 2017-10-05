<?php
// This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
//
// VPL for Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// VPL for Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with VPL for Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Show a VPL instance
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once dirname(__FILE__).'/../../../config.php';
require_once dirname(__FILE__).'/../vpl.class.php';

/**
 * Get the employed time for solving a problem vpl
 *
 * @param mixed $vpl object vpl or vpl identification
 * @param int $userid user identification
 * @param int $sgrade grade to consider the problem as solved
 * @param int $less max time limit
 * @param int $greater min time limit
 *
 * @return array with indexs time and info
 *         -> time : total_elapsed_time in milliseconds
 *         -> info : table with all information of processing time
 */
function get_solving_time($vpl, $userid, $sgrade = 8, $less = INF, $greater = 0) {
    global $DB;
    if (is_object($vpl)) {
        $instance = $vpl->get_instance();
        $vpl = $instance->id;
    }

    // get unix timestamp of submission
    $submission = $DB->get_record_sql('SELECT *
        FROM {vpl_submissions}
        WHERE grade >= :sgrade AND vpl = :vpl AND userid = :userid
              AND datesubmitted >= :greater AND datesubmitted <= :less
        ORDER BY id ASC', array('userid'=>$userid,
            'vpl'=>$vpl, 'sgrade'=>$sgrade, 'less'=>$less, 'greater'=>$greater));

    $solved_time = $submission->datesubmitted;
    $solved_grade = $submission->grade;

    $solved = false;
    $info_table = array();

    $accum_time = 0;
    $start_time = 0;
    $prev_elapsed_time = 0;
    $total_elapsed_time = 0;
    $client_start_recording_time = 0; 

    $code_recording_logs = $DB->get_records('vpl_code_recording_log'
        , array('vpl'=>$vpl, 'userid'=>$userid), $sort='id');

    foreach ($code_recording_logs as $cid=>$code_recording_log) {
        
        $server_recording_time = $code_recording_log->daterecorded;
        if ($server_recording_time > $less) continue; // cut timeout

        $code_logs = json_decode($code_recording_log->code);

        foreach ($code_logs as $pos=>$code_log) {

            if ($client_start_recording_time != $code_log->startTime) {

                $client_start_recording_time = $code_log->startTime;
                $end_code_log = end(array_merge($code_logs, array()));

                $client_recording_time = $client_start_recording_time + $end_code_log->elapsedTime;
                $delay = 1000*$server_recording_time - $client_recording_time;

                $accum_time += $prev_elapsed_time;
            }

            $server_start_time = $code_log->startTime + $delay;
            $server_code_time = $server_start_time + $code_log->elapsedTime;

            if ($server_start_time < $greater*1000) continue; // cut timeout
            if ($server_code_time > $less*1000) continue;

            if ($start_time == 0) $start_time = $server_start_time;

            if ($solved_time && ($server_code_time > $solved_time*1000)) {
                $solved = true;
                break;
            }

            $total_elapsed_time = $code_log->elapsedTime + $accum_time;
            $prev_elapsed_time = $code_log->elapsedTime;

            $info_table[$cid.$pos] = array(
                "client_start_time"=>$code_log->startTime,
                "server_start_time"=>$server_start_time,
                "client_code_time"=>$code_log->startTime + $code_log->elapsedTime,
                "server_code_time"=>$server_code_time,
                "total_elapsed_time"=>$total_elapsed_time,
                "files"=>$code_log->files
            );
        }

        if ($solved_time && $solved) { break; }
    }

    return array("time"=>$total_elapsed_time,
        "start_time"=>floor($start_time/1000),
        "solved_time"=>$solved_time,
        "solved_grade"=>$solved_grade,
        "info"=>$info_table);
}


/**
 * Processing information
 */
require_login();

$cmid = required_param('id', PARAM_INT); // id 
$userid = optional_param('userid', 0, PARAM_INT);
$less = optional_param('less', time(), PARAM_INT);
$greater = optional_param('greater', 0, PARAM_INT);

$sgrade = optional_param('grade', 8, PARAM_INT);
$show_fullinfo = optional_param('fullinfo', false, PARAM_BOOL);

$vpl = new mod_vpl($cmid);

$vplid = $vpl->get_instance()->id;

$select = 'vpl = :vpl';
$userids = $DB->get_fieldset_select('vpl_submissions', 'userid', $select, array('vpl'=>$vplid));
if ($userid != 0) $userids = array($userid);

// set PAGE
$cm = $vpl->get_course_module(); // get_coursemodule_from_id('vpl', $id); // course module
$cm_ins = context_module::instance($cm->id);
$course = $DB->get_record("course", array("id" =>$cm->course));
$instance = $DB->get_record('vpl', array("id" =>$cm->instance));

$PAGE->set_cm($cm, $course, $instance);
$PAGE->set_context($cm_ins);

$PAGE->set_url('/mod/vpl/editor/report.php',
    array('id'=>$cmid, 'less'=>$less, 'greater'=>$greater,
          'userid'=>$userid, 'fullinfo'=>$show_fullinfo));
$PAGE->set_title('Code Recording Logging');
$PAGE->set_heading($vpl->get_course()->fullname. ' - Code Recording Logging of VPL');

$table = new html_table();
$table->head = array ();
$table->head[] = 'UserID';
$table->head[] = 'ActivityID';
$table->head[] = 'StartTime';
$table->head[] = 'SolvedTime';
$table->head[] = 'SolvedGrade';
$table->head[] = 'SpendTime';

//echo "<th>UserID</th><th>ActivityID</th><th>StartTime</th><th>SolvedTime</th><th>SpendTime</th>";

if ($show_fullinfo) {
    $table->head[] = "Client StartTime (ms)";
    $table->head[] = "Server StartTime (ms)";
    $table->head[] = "Client EndCodeTime (ms)";
    $table->head[] = "Server EndCodeTime (ms)";
    $table->head[] = "Total ElapseTime (ms)";
    $table->head[] = "Code";

    //echo "<th>Client StartTime (ms)</th>";
    //echo "<th>Server StartTime (ms)</th>";
    //echo "<th>Client EndCodeTime (ms)</th>";
    //echo "<th>Server EndCodeTime (ms)</th>";
    //echo "<th>Total ElapseTime (ms)</th>";
    //echo "<th>Code</th>";
}

foreach (array_unique($userids) as $userid) {
    $resp = get_solving_time($vplid, $userid, $sgrade, $less, $greater);

    if ($show_fullinfo) {
        $row = array ();
        $row[] = $userid;
        $row[] = $cmid;
        $row[] = $resp["start_time"];
        $row[] = $resp["solved_time"];
        $row[] = $resp["solved_grade"];
        $row[] = floor($resp["time"]/1000);
        
        $table->data[] = $row;

        foreach ($resp["info"] as $info) {
            $row = array ();
            $row[] = '';
            $row[] = '';
            $row[] = '';
            $row[] = '';
            $row[] = '';
            $row[] = '';
            $row[] = $info["client_start_time"];
            $row[] = $info["server_start_time"];
            $row[] = $info["client_code_time"];
            $row[] = $info["server_code_time"];
            $row[] = $info["total_elapsed_time"];
            $row[] = $info["files"][0]->content;
            
            $table->data[] = $row;
        }
    } else {
        $row = array ();
        $row[] = $userid;
        $row[] = $cmid;
        $row[] = $resp["start_time"];
        $row[] = $resp["solved_time"];
        $row[] = $resp["solved_grade"];
        $row[] = floor($resp["time"]/1000);
        
        $table->data[] = $row;
    }
}

echo $OUTPUT->header();
echo html_writer::start_tag('div', array('class'=>'no-overflow'));
echo html_writer::table($table);
echo html_writer::end_tag('div');
 
echo $OUTPUT->footer();
