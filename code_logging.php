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
 * @package mod_vpl
 * @copyright 2016 Geiser Chalco
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Geiser Chalco <geiser@usp.br>
 */

require_once dirname(__FILE__).'/../../config.php';
require_once dirname(__FILE__).'/../../enrol/locallib.php';
header("Pragma: no-cache"); //Browser must reload page
require_login();

$id = required_param('id',PARAM_INT);
$userid = optional_param('userid', null, PARAM_INT);

$cm = get_coursemodule_from_id('vpl', $id); // course module
$cm_ins = context_module::instance($cm->id);
$course = $DB->get_record("course", array("id" =>$cm->course));
$instance = $DB->get_record('vpl', array("id" =>$cm->instance));

$PAGE->set_cm($cm, $course, $instance);
$PAGE->set_context($cm_ins);

$PAGE->set_url('/mod/vpl/code_logging.php', array('id'=>$id, 'userid'=>$userid));
$PAGE->set_title('Code logging of the VPL editor');
$PAGE->set_heading($course->fullname. '- Code logging of the VPL editor');

if (is_null($userid)) {

    $table = new html_table();
    $table->head = array ();
    $table->colclasses = array();
    $table->head[] = 'Nome Completo';
    $table->head[] = 'Email';
    $table->attributes['class'] = 'admintable generaltable';
    $table->colclasses[] = 'centeralign';
    $table->head[] = "";
    $table->colclasses[] = 'centeralign';

    $table->id = "users";

    $manager = new course_enrolment_manager($PAGE, $course);
    $users = $manager->get_users('firstname', 'ASC', 0, 0);

    foreach ($users as $user) {
        $params = array('sesskey'=>sesskey(), 'id'=>$id, 'userid'=>$user->id);
        $row = array ();
        $row[] = $user->firstname.' '.$user->lastname;
        $row[] = $user->email;

        $param['fullinfo'] = true;
        $row[] = html_writer::link(new moodle_url('/mod/vpl/code_logging.php', $params), 'Show Code Logging');

        $table->data[] = $row;
    }

    if (!empty($table)) {
        echo $OUTPUT->header();
        echo html_writer::start_tag('div', array('class'=>'no-overflow'));
        echo html_writer::table($table);
        echo html_writer::end_tag('div');
        echo $OUTPUT->footer();
    }
    die;
}

$table = new html_table();
$table->head = array ();
$table->head[] = 'Date Recorded';
$table->head[] = 'StartTime';
$table->head[] = "ElapseTime (ms)";
$table->head[] = "Code";

$code_recording_logs = $DB->get_records('vpl_code_recording_log'
    , array('vpl'=>$instance->id, 'userid'=>$userid));
foreach ($code_recording_logs as $cid=>$code_recording_log) {
    $code_logs = json_decode($code_recording_log->code);
    foreach ($code_logs as $pos=>$code_log) {
        $row = array();
        $row[] = date(DATE_RFC2822, $code_recording_log->daterecorded);
        $row[] = date(DATE_RFC2822, floor($code_recording_log->startTime/1000));
        $row[] = $code_log->elapsedTime;
        $row[] = '<pre>'.$code_log->files[0]->content.'</pre>';

        $table->data[] = $row;
    }
}

echo $OUTPUT->header();
echo html_writer::start_tag('div', array('class'=>'no-overflow'));
echo html_writer::table($table);
echo html_writer::end_tag('div');

echo $OUTPUT->footer();
