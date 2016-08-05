/**
 * coreがあるディレクトリ, 通常はpublic_html
 */
var basePath = '../public_html/';

/**
 * 配布用ファイルの吐き出し先
 */
var distPath = basePath + 'dist/';

/**
 * 本体
 */
module.exports = function(grunt){
    /**
     * Grunt設定
     */
    grunt.initConfig({
    });

    // プラグインのロード
    grunt.loadNpmTasks('grunt-contrib-concat');
    grunt.loadNpmTasks('grunt-contrib-cssmin');
    grunt.loadNpmTasks('grunt-contrib-jshint');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-less');
    grunt.loadNpmTasks('grunt-contrib-watch');

    grunt.loadNpmTasks('grunt-notify');
    grunt.task.run('notify_hooks');

    // 実施するタスクの登録
    grunt.registerTask('default', ['watch']);

    grunt.registerTask('build', [
    ]);
};