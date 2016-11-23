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

require_once dirname(__FILE__).'/../../../config.php';
require_once dirname(__FILE__).'/../../../enrol/locallib.php';
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

$PAGE->set_url('/mod/vpl/editor/live_stream.php',array('id'=>$id, 'userid'=>$userid));
$PAGE->set_title('Screen recording video of the VPL editor');
$PAGE->set_heading($course->fullname. '- Screen recording video of the VPL editor');

if (is_null($userid)) {
    
    $table = new html_table();
    $table->head = array ();
    $table->colclasses = array();
    $table->head[] = 'Nome Completo';
    $table->head[] = 'Email';
    $table->attributes['class'] = 'admintable generaltable';
    $table->colclasses[] = 'centeralign';
    $table->head[] = "";
    $table->head[] = "";
    $table->head[] = "";
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
        
        $params['since'] = -1;
        $row[] = html_writer::link(new moodle_url('/mod/vpl/editor/live_stream.php', $params), 'Desde ó Início');
        
        $params['since'] = time() - 5*60;
        $row[] = html_writer::link(new moodle_url('/mod/vpl/editor/live_stream.php', $params), 'Desde 5 min Atrás');
        
        $params['since'] = time() - 15*60;
        $row[] = html_writer::link(new moodle_url('/mod/vpl/editor/live_stream.php', $params), 'Desde 15 min Atrás');
        
        $params['since'] = time();
        $row[] = html_writer::link(new moodle_url('/mod/vpl/editor/live_stream.php', $params), 'Live Streaming');
        
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

$ajaxUrl = new moodle_url('/mod/vpl/editor/recording_ajax.php', array('sesskey'=>sesskey()));
$videoUrl = new moodle_url('/mod/vpl/editor/video.php', array('sesskey'=>sesskey()));

$sincetime = required_param('since',PARAM_INT);
$videoids = $DB->get_fieldset_select('vpl_screen_recording_log', 'id',
    'cmid = :cmid AND vpl = :vpl AND userid = :userid AND daterecorded >= :sincetime',
    array('cmid'=>$cm->id,
          'vpl'=>$cm->instance,
          'userid'=>$userid,
          'sincetime'=>$sincetime));

$plugincfg = get_config('mod_vpl');
if (!empty($plugincfg->loadvideolisttime)) {
    $PAGE->requires->js_call_amd('mod_vpl/video_streaming',
        'setLoadVideoListTime', array('loadVideoListTime'=>$plugincfg->loadvideolisttime));
}

if (empty($videoids)) $videoids = array();
$PAGE->requires->js_call_amd('mod_vpl/video_streaming',
    'initLiveStream', array('id'=>'myvideo',
                            'ajaxUrl' => $ajaxUrl->out(),
                            'videoUrl'=> $videoUrl->out(), 
                            'cmid'=>$cm->id,
                            'vpl'=>$cm->instance,
                            'userid'=>$userid,
                            'sincetime'=>time(),
                            'videoids'=>$videoids));

echo $OUTPUT->header();
?><video id="myvideo" width="640" height="480" <?php if(optional_param('control',false,PARAM_BOOL)) echo 'controls'; ?> style="background:black"></video><?php
echo $OUTPUT->footer();

