/**
 * Virtual Programming Lab module
 *
 * @package    mod_vpl
 * @copyright  Geiser Chalco <geiser@usp.br>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module mod_vpl/code_logging
 */
define(["jquery"], function($) {
    
    var codeRecordingTime = 3000;
    var autoSaveCodeRecordingTime = 0;

    var screenRecordingTime = 3000;
    var autoSaveScreenRecordingTime = 0;

    return /** @alias module:mod_vpl/code_logging */ {

        setOptions: function(codeRecordingTime, autoSaveCodeRecordingTime,
                        screenRecordingTime, autoSaveScreenRecordingTime) {
            codeRecordingTime = codeRecordingTime;
            autoSaveCodeRecordingTime = autoSaveCodeRecordingTime;

            screenRecordingTime = screenRecordingTime;
            autoSaveScreenRecordingTime = autoSaveScreenRecordingTime;
        },

        /**
         * Initialize the code recording for the logging
         *
         * @param {nextSteps} array of ids or alias for the next steps
         */
        initCodeRecording: function(id, url, cmid, userid) {

            var codeRecordingTimeout;
            var autoSaveCodeRecordingTimeout;

            var isCodeRecording = false;
            var codeData = new Array();
            var startTime = $.now();

            var isVPLReady = function() {
                return (window.vpl_files != undefined &&
                        window.vpl_files != null &&
                        window.vpl_files.length > 0);
            }
            
            // looper to code recording
            var codeRecordingLooper = function() {
                if (!isVPLReady()) {
                    codeRecordingTimeout = setTimeout(codeRecordingLooper, codeRecordingTime);
                    return;
                }
                //
                var files = new Array();
                for (var i = 0; i < window.vpl_files.length; i++) {
                    files.push({
                        fileName: window.vpl_files[i].getFileName(),
                        content: window.vpl_files[i].getContent()
                    });
                }
                //
                codeData.push({
                    startTime: startTime,
                    elapsedTime: $.now() - startTime,
                    files: files
                });
                codeRecordingTimeout = setTimeout(codeRecordingLooper, codeRecordingTime);
            };

            // looper to auto save data
            var autoSaveCodeRecordingLooper = function() {
                if (!isVPLReady()) {
                    autoSaveCodeRecordingTimeout = setTimeout(autoSaveCodeRecordingLooper, autoSaveCodeRecordingTime);
                    return;
                }
                stopCodeRecording(function() {
                    saveCodeRecording(function() { 
                        codeData = new Array();
                        startCodeRecording();
                    });
                });
                autoSaveCodeRecordingTimeout = setTimeout(autoSaveCodeRecordingLooper, autoSaveCodeRecordingTime);
            };

            // save code recording
            var saveCodeRecording = function(callback) {
                if (codeData.length > 0) {
                    // send to server to save data
                    $.ajax({
                        url: url,
                        data: {
                            cmid: cmid,
                            userid: userid,
                            action: "savecoderecording",
                            codeData: codeData
                        }, method: "POST"
                    }).done(function() {
                        codeData = new Array(); callback();
                    }).fail(function() {
                        console.log("ERROR: Can't send data "+codeData+" to "+url);
                    });
                }
            };

            // stop code recording
            var stopCodeRecording = function(callback) {
                isCodeRecording = false;
                clearTimeout(codeRecordingTimeout);
                if (autoSaveCodeRecordingTime > 0) {
                    clearTimeout(autoSaveCodeRecordingTimeout);
                }
                if (callback != undefined && callback != null) {
                    callback();
                }
            };

            // start code recording
            var startCodeRecording = function() {
                if (!isCodeRecording) {
                    codeRecordingLooper();
                    if (autoSaveCodeRecordingTime > 0) {
                        autoSaveCodeRecordingLooper();
                    }
                }
                isCodeRecording = true;
            };

            // stop and save data when a user blur the page
            $(window).blur(function() {
                stopCodeRecording(function() {
                    saveCodeRecording(function() { });
                });
            });

            // stop and save data when a user navigate away from the page
            /*$(window).unload(function() {
                stopCodeRecording(function() {
                    saveCodeRecording(function() { });
                });
            });*/
 
            $(window).on("beforeunload", function(){
                stopCodeRecording(function() {
                    saveCodeRecording(function() { });
                });
                return "Are you sure you want to leave this page?";
            });
                      
            // start recording when the windows is load
            $(window).load(function() {
                startCodeRecording();
            });

            // start recording again when a user focus the page
            $(window).focus(function() {
                startCodeRecording();
            });

        },

        /**
         * Initialize the screen recording for the logging
         *
         * @param {nextSteps} array of ids or alias for the next steps
         */
        initScreenRecording: function(id, url, cmid, userid) {

            var screenRecordingTimeout;
            var autoSaveScreenRecordingTimeout;

            var isScreenRecording = false;
            var elementToShare;
            var recorder; 
            var canvas2d;
            var context; 
            
            // is recording ready
            var isScreenRecordingReady = function() {
                return (recorder != undefined && recorder != null &&
                        canvas2d != undefined && canvas2d != null &&
                        context != undefined && context != null);
            };

            // save screen recording
            var saveScreenRecording = function(callback) {
                if (!isScreenRecordingReady()) return;

                recorder.stopRecording(function() {
                    var blob = recorder.getBlob();

                    var fd = new FormData();
                    fd.append('video-filename', 'video.webm');
                    fd.append('video-blob', blob);
                    fd.append('action', "savescreenrecording");
                    fd.append('cmid', cmid);
                    fd.append('userid', userid);

                    $.ajax({
                        url: url,
                        data: fd,
                        processData: false,
                        contentType: false,
                        method: "POST"
                    }).done(callback);
                });
            };

            // looper to auto save data
            var autoSaveScreenRecordingLooper = function() {
                if (!isScreenRecordingReady()) {
                    autoSaveScreenRecordingTimeout = setTimeout(autoSaveScreenRecordingLooper,
                            autoSaveScreenRecordingTime);
                    return;
                }
                stopScreenRecording(function() {
                    saveScreenRecording(function() { 
                        startScreenRecording();
                    });
                });
                autoSaveScreenRecordingTimeout = setTimeout(autoSaveScreenRecordingLooper,
                        autoSaveScreenRecordingTime);
            };

            // looper to screen recording (convert html5 to jpeg)
            var screenRecordingLooper = function() {
                if (!isScreenRecordingReady()) {
                    screenRecordingTimeout = setTimeout(screenRecordingLooper, screenRecordingTime);
                    return;
                }
                //
                html2canvas(elementToShare, {
                    grabMouse: false,
                    onrendered: function(canvas) {
                        context.clearRect(0, 0, canvas2d.width, canvas2d.height);
                        context.drawImage(canvas, 0, 0, canvas2d.width, canvas2d.height);
                        screenRecordingTimeout = setTimeout(screenRecordingLooper, screenRecordingTime);
                    }
                });
            };

            // stop screen recording
            var stopScreenRecording = function(callback) {
                isScreenRecording = false;
                clearTimeout(screenRecordingTimeout);
                if (autoSaveScreenRecordingTime > 0) {
                    clearTimeout(autoSaveScreenRecordingTimeout);
                }
                if (callback != undefined && callback != null) {
                    callback();
                }
            };

            // start screen recording
            var startScreenRecording = function() {
                if (!isScreenRecording) {
                    
                    if (elementToShare == undefined || elementToShare == null) {
                        elementToShare = document.getElementById(id);
                    }

                    if (canvas2d == undefined || canvas2d == null) {
                        canvas2d = document.createElement('canvas');
                        canvas2d.width = elementToShare.clientWidth;
                        canvas2d.height = elementToShare.clientHeight;
                        canvas2d.style.top = 0;
                        canvas2d.style.left = 0;
                        canvas2d.style.zIndex = -1;

                        (document.body || document.documentElement).appendChild(canvas2d);
                        canvas2d.style.visibility = "hidden";    
                    }

                    context = canvas2d.getContext('2d');
                    recorder = new RecordRTC(canvas2d, { type: 'canvas' });
                    recorder.startRecording();

                    screenRecordingLooper();
                    if (autoSaveScreenRecordingTime > 0) {
                        autoSaveScreenRecordingLooper();
                    }
                }
                isScreenRecording = true;
            };

            // stop and save data when a user blur the page
            $(window).blur(function() {
                stopScreenRecording(function() {
                    saveScreenRecording(function() { });
                });
            });

            // stop and save data when a user navigate away from the page
            /*$(window).unload(function() {
                stopScreenRecording(function() {
                    saveScreenRecording(function() { });
                });
            });*/

            $(window).on("beforeunload", function(){
                stopScreenRecording(function() {
                    saveScreenRecording(function() { });
                });
                return "Are you sure you want to leave this page?";
            });
            
            // start recording when the windows is load
            $(window).load(function() {
                startScreenRecording();
            });

            // start recording again when a user focus the page
            $(window).focus(function() {
                startScreenRecording();
            });

        }

    };

});

