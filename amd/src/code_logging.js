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
    
    var codeCaptureTime = 5000;
    var screenCaptureTime = 1000;
    var screenCaptureAutoSaveTime = 60000;
    var loadVideoListTime = 60000;

    return /** @alias module:mod_vpl/code_logging */ {

        setLoadVideoListTime: function (loadVideoListTime) {
            loadVideoListTime = loadVideoListTime;
        },

        setOptions: function(codeCaptureTime, screenCaptureTime, screenCaptureAutoSaveTime) {
            codeCaptureTime = codeCaptureTime;
            screenCaptureTime = screenCaptureTime;
            screenCaptureAutoSaveTime = screenCaptureAutoSaveTime;
        },

        /**
         * Initialize the code capture for the logging
         *
         * @param {nextSteps} array of ids or alias for the next steps
         */
        initCodeCapture: function(id) {

            var lastTime = $.now();
            var lastContents = new Array();

            // infinity loop to convert html in image 
            var looperCodeCapture = function() {
                if (window.vpl_files == undefined) {
                    return setTimeout(looperCodeCapture, codeCaptureTime);
                }

                for (var i = 0; i < window.vpl_files.length; i++) {
                    if (window.vpl_files_log[i] == undefined) {
                        window.vpl_files_log[i] = new Array();
                    }
                    if (lastContents[i] != window.vpl_files[i].getContent()) {
                        window.vpl_files_log[i].push({
                            time: {from: lastTime, to: $.now()},
                            content: window.vpl_files[i].getContent(),
                            fileName: window.vpl_files[i].getFileName()
                        });
                        lastContents[i] = window.vpl_files[i].getContent();
                    }
                }
                lastTime = $.now();
                setTimeout(looperCodeCapture, codeCaptureTime);
            };
             
            $(window).load(function() {
                window.vpl_files_log = new Array();
                window.vpl_code_capture_enabled = true;
                looperCodeCapture();
            });
        },

        /**
         * Initialize the screen capture for the logging
         *
         * @param {nextSteps} array of ids or alias for the next steps
         */
        initScreenCapture: function(id, url, cmid, userid) {
            
            var recorder; 
            var isRecordingStarted = false;
            var isStoppedRecording = false;
            var lastAutoSaveTime = $.now();
            
            var elementToShare = document.getElementById(id);
            var canvas2d = document.createElement('canvas');
            var context = canvas2d.getContext('2d');

            canvas2d.width = elementToShare.clientWidth;
            canvas2d.height = elementToShare.clientHeight;
            canvas2d.style.top = 0;
            canvas2d.style.left = 0;
            canvas2d.style.zIndex = -1;
                
            (document.body || document.documentElement).appendChild(canvas2d);
            canvas2d.style.visibility = "hidden";
            
            webrtcUtils.enableLogs = false;

            var stopAndSaveRecording = function(callback) {
                if (!isRecordingStarted) return;
                isRecordingStarted = false;
                isStoppedRecording = true;

                recorder.stopRecording(function() {
                    var blob = recorder.getBlob();

                    var fd = new FormData();
                    fd.append('video-filename', 'video.webm');
                    fd.append('video-blob', blob);
                    fd.append('action', "savescreenrecord");
                    fd.append('cmid', cmid);
                    fd.append('userid', userid);

                    $.ajax({
                        url: url,
                        data: fd,
                        processData: false,
                        contentType: false,
                        method: "POST"
                    }).done(function(msg) {
                        lastAutoSaveTime = $.now();
                        callback();
                        // if (resume) webgazer.resume();
                    }).fail(function() {
                        // console msg
                        // if (resume) webgazer.resume();
                    }).always(function() {
                        // if (resume) webgazer.resume();
                        // resume recording
                    });
                });
            };

            // infinity loop to convert html in image 
            var looperScreenCapture = function() {
                if (isStoppedRecording) return;
                if (!isRecordingStarted) {
                    return setTimeout(looperScreenCapture, screenCaptureTime);
                }

                html2canvas(elementToShare, {
                    grabMouse: false,
                    onrendered: function(canvas) {
                        context.clearRect(0, 0, canvas2d.width, canvas2d.height);
                        context.drawImage(canvas, 0, 0, canvas2d.width, canvas2d.height);
                        setTimeout(looperScreenCapture, screenCaptureTime);
                    }
                });
            };

            // infinity loop for autosave sending information to the server
            var looperScreenCaptureAutoSave = function() {
                if (isStoppedRecording) return;
                if (!isRecordingStarted) {
                    return setTimeout(looperScreenCaptureAutoSave, screenCaptureAutoSaveTime);
                }
                    
                if ($.now() - lastAutoSaveTime >= screenCaptureAutoSaveTime) {
                    stopAndSaveRecording(function() {
                        initRecording();
                    });
                }
                setTimeout(looperScreenCaptureAutoSave, screenCaptureAutoSaveTime);
            };

            var initRecording = function() {
                if (isRecordingStarted) return;
                isStoppedRecording = false;

                recorder = new RecordRTC(canvas2d, { type: 'canvas' });
                recorder.startRecording();
                
                looperScreenCapture();
                looperScreenCaptureAutoSave();
                isRecordingStarted = true;
            };
            
            // start recording when the windows is load
            $(window).load(function() {
                initRecording();
            });

            // stop and save data when a user blur the page
            $(window).blur(function() {
                stopAndSaveRecording(function() {
                    // don't nothing
                });
            });

            // stop and save data when a user navigate away from the page
            $(window).unload(function() {
                stopAndSaveRecording(function() {
                        // don't nothing
                });
            });

            // start recording again when a user focus the page
            $(window).focus(function() {
                initRecording();
            });

        },

        /**
         * Initialize the live Stream fom screen capture for the logging
         *
         * @param {nextSteps} array of ids or alias for the next steps
         */
        initLiveStream: function(id, ajaxUrl, videoUrl, vpl, userid, sincetime, videoids) {

            var myvid = null;
            var activeVideo = 0;
            var videoListIds = [];
            var startTime = sincetime;
            var existMoreVideos = false;

            // play live video list
            var playNextVideo = function() {
                if (videoListIds.length > activeVideo) {
                    myvid.src = videoUrl + '&id=' + videoListIds[activeVideo];
                    myvid.play();
                    activeVideo++;
                } else {
                    existMoreVideos = false;
                }
            };

            // infinity loop to obtain videoids
            var looperLoadVideoList = function() {
                $.ajax({
                    url: ajaxUrl,
                    data: {
                        action: "listlivestreamvideoids",
                        sincetime: startTime,
                        vpl: vpl,
                        userid: userid
                    }, method: "POST"
                }).done(function(result) {
                    videoListIds = videoListIds.concat(result.videoids); 
                    startTime = result.newtime;

                    if (existMoreVideos) {
                    } else if (result.videoids.length > 0) {
                        existMoreVideos = true;
                        playNextVideo();
                    }
                });
                setTimeout(looperLoadVideoList, loadVideoListTime);
            };

            // load function
            $(window).load(function() {
                myvid = document.getElementById(id);
                myvid.addEventListener('ended', function(e) {
                    playNextVideo(); 
                });

                // videoids 
                if (videoids.length > 0) {
                    videoListIds = videoids;
                    existMoreVideos = true;
                    playNextVideo();
                }
                looperLoadVideoList();
            });
        }
    };

});

