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
//if (!defined('AJAX_SCRIPT')) {
//    define('AJAX_SCRIPT', true);
//}

require(__DIR__.'/../../../config.php');

$id = required_param('id', PARAM_TEXT);
//require_sesskey();

$screen_recording_log = $DB->get_record('vpl_screen_recording_log', array('id' => $id));

if ($screen_recording_log) {
    header('Content-Type: video/webm');
    echo($screen_recording_log->video);
}

die;
