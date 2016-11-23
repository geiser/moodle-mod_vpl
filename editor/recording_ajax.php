<?php
// This file is based on part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Process ajax requests in the editor related to screen record
 *
 * @copyright Geiser Chalco
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mod_vpl
 */
if (!defined('AJAX_SCRIPT')) {
    define('AJAX_SCRIPT', true);
}

require(__DIR__.'/../../../config.php');

$action = required_param('action', PARAM_TEXT);
require_sesskey();

$return = false;

if ($action == "savescreenrecording") {
    if ($_FILES["video-blob"]["size"] > 0) {
        $cm = get_coursemodule_from_id('vpl', required_param('cmid', PARAM_INT));

        $screen_recording_log = new stdClass();
        $screen_recording_log->cmid = $cm->id;
        $screen_recording_log->vpl = $cm->instance;
        $screen_recording_log->userid = required_param('userid', PARAM_INT);
        $screen_recording_log->daterecorded = time();
        $screen_recording_log->video = file_get_contents($_FILES["video-blob"]["tmp_name"]);

        $return = $DB->insert_record('vpl_screen_recording_log', $screen_recording_log);
        $return = !empty($return) ? true : false;
    }

} else if ($action == "savecoderecording") {
    $cm = get_coursemodule_from_id('vpl', required_param('cmid', PARAM_INT));

    $code_recording_log = new stdClass();
    $code_recording_log->cmid = $cm->id;
    $code_recording_log->vpl = $cm->instance;
    $code_recording_log->userid = required_param('userid', PARAM_INT);
    $code_recording_log->daterecorded = time();
    $code_recording_log->code = json_encode($_POST['codeData']);
 
    $return = $DB->insert_record('vpl_code_recording_log', $code_recording_log);
    $return = !empty($return) ? true : false;
} else if ($action == "listlivestreamvideoids") {
    $sincetime = required_param('sincetime', PARAM_INT);
    $cmid = required_param('cmid', PARAM_INT);
    $vpl = required_param('vpl', PARAM_INT);
    $userid = required_param('userid', PARAM_INT);
    $newtime = time();

    $videoids = $DB->get_fieldset_select('vpl_screen_recording_log', 'id',
        'cmid = :cmid AND vpl = :vpl AND userid = :userid AND '.
        'daterecorded >= :fromtime AND daterecorded < :totime',
        array('vpl'=>$vpl,
              'cmid'=>$cmid,
              'userid'=>$userid,
              'fromtime'=>$sincetime,
              'totime'=>$newtime));

    $return = new stdClass();
    $return->videoids = empty($videoids) ? array() : $videoids;
    $return->newtime = $newtime;
}

echo json_encode($return);
die;
