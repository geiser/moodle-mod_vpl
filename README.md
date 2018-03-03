= VPL - Virtual Programming Lab for Moodle 2 =

VPL is a programming assignment management system that lets edit and execute programs and enable the automatic and continuous assessment. This software is distributed under the terms of the General Public License (see http://www.gnu.org/licenses/gpl.txt for details)

This software is provided "AS IS" without a warranty of any kind.

For more detail access the web site http://vpl.dis.ulpgc.es

= Tables =


- table: mdl_vpl_code_recording_log (add)

| Column Name | Type | Comment |
|--------------|----------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| id | int |  |
| cmid | int | Course Module ID stored in mdl_course_modules table as ID used look where a module was instantiate in a course  |
| vpl | int | VPL ID stored in mdl_vpl table as ID for the programming Lab |
| userid | int | User ID stored in mdl_user as ID for the participant of course |
| daterecorded | unix timestamp | Data/time for the recording in unix timestamp |
| code | json list | Logging from VPL environment in list format [record1, record2, ...., recordN ], where each element of the list contains: {    startTime: ${unix timestamp in mili-second when the user start an interaction with the VPL environment},   elapsedTime: ${duration in mili-second for the interaction with the VPL environment},   files: ${list of files modified with the interaction [file-record1, file-record2, ..., file-recordM], where each file-record is recording as:      {         fileName: ${file name}        content: ${content of the file}       }   } } |




