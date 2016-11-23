"use strict";
 
module.exports = function (grunt) {
    // Load all grunt tasks.
    grunt.loadNpmTasks("grunt-contrib-less");
    grunt.loadNpmTasks("grunt-contrib-watch");
    grunt.loadNpmTasks("grunt-contrib-clean");
    grunt.loadNpmTasks("grunt-contrib-uglify");
 
    grunt.initConfig({
        uglify: {
            my_target: {
                files: {'amd/build/code_logging.min.js': ['amd/src/code_logging.js'],
                        'amd/build/video_streaming.min.js': ['amd/src/video_streaming.js']}
            }
        },
        watch: {
            // If any .less file changes in directory "less" then run the "less" task.
            files: "less/*.less",
            tasks: ["less"]
        },
        less: {
            // Production config is also available.
            development: {
                options: {
                    // Specifies directories to scan for @import directives when parsing.
                    // Default value is the directory of the source, which is probably what you want.
                    paths: ["less/"],
                    compress: true
                },
                files: {
                    "styles.css": "less/styles.less"
                }
            },
        }
    });
    // The default task (running "grunt" in console).
    grunt.registerTask("default", ["less"]);
};

