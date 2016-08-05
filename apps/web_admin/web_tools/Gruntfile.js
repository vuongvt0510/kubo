var cssPath = '../public_html/assets/layouts/layout/css';
var jsPath = '../public_html/assets/layouts/layout/scripts';

module.exports = function (grunt) {

    // Project configuration.
    grunt.initConfig({
        // min css
        cssmin: {
            target: {
                files: [{
                    expand: true,
                    cwd: cssPath,
                    src: ['*.css', '!*.min.css', '**/*.css', '**/!*.min.css'],
                    dest: cssPath,
                    ext: '.min.css'
                }]
            }
        },
        watch: {
            appcss: {
                files: [
                    cssPath + '/*.css',
                    cssPath + '/**/*.css'
                ],
                tasks: ['cssmin']
            }
        }
    });

    // Load the plugin that provides the "uglify" task.
    grunt.loadNpmTasks('grunt-contrib-cssmin');
    grunt.loadNpmTasks('grunt-sass');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-notify');

    // Default task(s).
    grunt.registerTask('default', ['watch']);

    grunt.registerTask('build', [
        'cssmin'
    ]);
};