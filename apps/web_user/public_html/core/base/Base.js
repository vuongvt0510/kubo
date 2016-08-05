AppCore = window.AppCore || {};
AppCore.Base = AppCore.Base || {};

(function () {

    AppCore.Config = {
        baseUrl: '/core'
    };

    AppCore.Base = Backbone.View.extend({

        requireJavaScript: [],

        requireCSS: [],

        initialize: function () {
            this.loader();
        },

        /**
         * Loader function for JS and CSS
         */
        loader: function () {
            var baseUrl = AppCore.Config.baseUrl;

            _.each(this.requireJavaScript, function (v) {
                $.getScript(baseUrl + v);
            }, this);

            _.each(this.requireCSS, function (v) {
                $('head').append(
                    '<link rel="stylesheet" type="text/css" href="' + baseUrl + v + '" />'
                );
            }, this);

            $.getScript(baseUrl + '/third_party/bootbox/bootbox.js', function () {
                window.bootbox.setDefaults('locale', 'ja');
            });
            $.getScript(baseUrl + '/third_party/ladda-bootstrap/dist/spin.min.js');
        },

        /**
         * Alert by bootbox.js
         */
        alert: function () {
            window.bootbox.alert.apply(this, arguments);
        },

        /**
         * Confirm by bootbox.js
         */
        confirm: function () {
            window.bootbox.confirm.apply(this, arguments);
        },

        /**
         * Prompt by bootbox.js
         */
        prompt: function () {
            window.bootbox.prompt.apply(this, arguments);
        },

        /**
         * Dialog by bootbox.js
         */
        dialog: function () {
            window.bootbox.dialog.apply(this, arguments);
        },

        /**
         * Modal Dialog by bootbox.js
         */
        modal: function (config) {
            config = config || {};

            if (config.url) {
                var id = "modal-id-" + Math.floor(Math.random() * 100);
                config.message = '<div class="' + id + '"></div>';

                this.dialog.apply(this, arguments);
                var el = $('.' + id).css('min-height', 50);

                /**
                 * Run ajax request to fetch modal
                 */

                new Spinner({
                    color: '#CCC',
                    width: 3,
                    scale: 0.25
                }).spin(el[0]);

                $.ajax({
                    url: config.url,
                    data: config.data || {},
                    html: true
                }).success(function (res) {

                    if (!res) {
                        el.html('コンテンツの取得ができませんでした');
                        return;
                    }

                    el.css('min-height', 'auto').html(res);
                }).error(function () {
                    el.html('コンテンツの取得ができませんでした');
                });

                return;
            }

            this.dialog.call(this, arguments);
        }

    });

})();


(function($) {

    /**
     * nl2br function for jquery
     *
     * @param str
     * @param is_xhtml
     * @returns {string}
     */
    $.extend({
        nl2br: function (str, is_xhtml) {
            var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br />' : '<br>';
            return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2');
        }
    });

})(jQuery);