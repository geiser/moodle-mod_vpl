/**
 * Virtual Programming Lab module
 *
 * @package    mod_vpl
 * @copyright  Geiser Chalco <geiser@usp.br>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module mod_vpl/video_streaming
 */
define(["jquery"], function($) {

    var loadVideoListTime = 60000;

    return /** @alias module:mod_vpl/code_logging */ {

        setLoadVideoListTime: function (loadVideoListTime) {
            loadVideoListTime = loadVideoListTime;
        },

        /**
         * Initialize the live Stream fom screen capture for the logging
         *
         * @param {nextSteps} array of ids or alias for the next steps
         */
        initLiveStream: function(id, ajaxUrl, videoUrl, cmid, vpl, userid, sincetime, videoids) {

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
                        cmid: cmid,
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
