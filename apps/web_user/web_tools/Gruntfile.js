var basePath = '../public_html/';
var distPath = basePath + 'dist/';
var commonPath = basePath + '';
var bowerPath = './bower_components/';
var npmPath = './node_modules';
var corePath = basePath + 'core/';
var coreThirdParty = basePath + 'third_party/';

module.exports = function (grunt) {

    // Project configuration.
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        concat: {
            options: {
                banner: '<%= banner %>\n<%= jqueryCheck %>\n<%= jqueryVersionCheck %>',
                stripBanners: false
            },
            corejs: {
                src: [
                    //coreThirdParty + 'jquery.min.js',
                    //coreThirdParty + 'bootstrap-sass/assets/javascripts/bootstrap.min.js',
                    coreThirdParty + 'jquery.placeholder.js',
                    coreThirdParty + 'modernizr.js',
                    coreThirdParty + 'bootbox.js'
                ],
                dest: distPath + 'core.js'
            },
            appjs: {
                src: [
                    //basePath + 'js/**/*.js'
                    basePath + 'js/common.js'
                ],
                dest: distPath + 'app.js'
            },


            appcss: {
                src: [
                    basePath + 'css/style.css'
                ],
                dest: distPath + 'app.css'
            },

            // Setup material design
            materialjs: {
                src: [
                    coreThirdParty + 'bootstrap-material-design/dist/js/*.js'
                ],
                dest: distPath + 'material.js'
            }
        },
        // min css
        cssmin: {
            options: {
                yuicompress: true
            },
            app: {
                src: [distPath + 'app.css'],
                dest: distPath + 'app.min.css'
            }
        },
        // min js
        uglify: {
            options: {
                compress: true
            },
            coremin: {
                src: distPath + 'core.js',
                dest: distPath + 'core.min.js'
            },
            appmin: {
                src: distPath + 'app.js',
                dest: distPath + 'app.min.js'
            },
            materialmin: {
                src: distPath + 'material.js',
                dest: distPath + 'material.min.js'
            }
        },
        jshint: {
            app: {
                files: {
                    src: [
                        basePath + 'js/ST.Base.js'
                    ]
                }
            }
        },
        //less: {
        //    development: {
        //        options: {
        //            //compress: true,
        //            yuicompress: true,
        //            optimization: 2
        //        },
        //        files: {
        //            "../public_html/css/style-ie.css": "../public_html/less/style-ie.less",
        //            "../public_html/css/style.css": "../public_html/less/style.less" // destination file and source file
        //        }
        //    }
        //},
        // Compiles Sass to CSS and generates necessary files if requested
        sass: {
            options: {
                sourceMap: true,
                sourceMapEmbed: false,
                sourceMapContents: true,
                includePaths: ['.']
            },
            dist: {
                files: [{
                    expand: true,
                    cwd: basePath + '/scss',
                    src: ['*.{scss,sass}'],
                    dest: basePath + '/css',
                    ext: '.css'
                }]
            }
        },
        watch: {
            appjs: {
                files: [
                    basePath + 'js/*.js',
                    basePath + 'js/**/*.js'
                ],
                tasks: [
                    'jshint:app',
                    'concat:appjs',
                    'uglify:coremin',
                    'uglify:appmin',
                    'uglify:materialmin'
                ]
            },
            appcss: {
                files: [
                    //basePath + 'less/*.less',
                    //basePath + 'less/*/*.less',
                    basePath + 'scss/*.{scss,sass}',
                    basePath + 'scss/**/*.{scss,sass}'
                ],
                tasks: [
                    //'less:development',
                    'sass',
                    'concat:appcss',
                    'cssmin:app'
                ]
            }
        },
        imagemin: {
            dynamic: {
                optimizationLevel: 7,
                files: [{
                    expand: true,
                    cwd: basePath + 'images_temp/',
                    src: ['**/*.{png,jpg,gif,svg}'],
                    dest: basePath + 'images/'
                }]
            }
        }
    });

    // Load the plugin that provides the "uglify" task.
    grunt.loadNpmTasks('grunt-contrib-concat');
    grunt.loadNpmTasks('grunt-contrib-cssmin');
    grunt.loadNpmTasks('grunt-contrib-jshint');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    //grunt.loadNpmTasks('grunt-contrib-less');
    grunt.loadNpmTasks('grunt-sass');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-contrib-imagemin');

    grunt.loadNpmTasks('grunt-notify');

    //grunt.loadNpmTasks('grunt-styledocco');

    // Default task(s).
    grunt.registerTask('default', ['watch']);

    grunt.registerTask('buildcss', [
        'sass',
        'concat',
        'cssmin'
    ]);

    grunt.registerTask('build', [
        //'less',
        'sass',
        'jshint',
        'concat',
        'cssmin',
        'uglify',
        'imagemin'
    ]);
};