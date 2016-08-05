AppCore = window.AppCore || {};
AppCore.Form = AppCore.Form || {};

(function () {

    AppCore.Form = AppCore.Base.extend({

        requireJavaScript: [
            '/third_party/ladda-bootstrap/dist/spin.min.js',
            '/third_party/ladda-bootstrap/dist/ladda.min.js'
        ],

        requireCSS: [
            '/third_party/ladda-bootstrap/dist/ladda-themeless.min.css'
        ],

        errorTemplate: _.template([
            '<div>',
                '<p class="text-danger help-text"><%= error %></p>',
            '</div>'
        ].join('')),

        cancelMessage: 'このページを離れると、入力したデータが削除されます。\nよろしいですか？',

        /**
         * Initialize
         * @param {Object} config
         */
        initialize: function (config) {
            config = config || {};

            this.loader();
            this.render(config);
        },

        /**
         * Render
         *
         * @param {Object} config
         */
        render: function (config) {
            config = config || {};

            /**
             * Configure submit button for ladda
             */
            var button = this.$('input[type=submit],button[type=submit]', this.$el);
            button.addClass('ladda-button');
            button.attr('data-style', 'expand-right');

            var span = $('<span />').addClass('ladda-label').html(button.html());
            button.empty().append(span);

            /**
             * @type {boolean} Handling Cancel button is used right now
             */
            var isCanceling = false;

            /**
             * @type {Object} Handle cancel jquery object
             */
            var cancelLink = $('.form-cancel', this.$el);
            if (cancelLink) {
                var href = cancelLink.attr('href');
                cancelLink.attr('href', 'javascript:;').on('click', _.bind(function () {
                    this.confirm($.nl2br(this.cancelMessage), function (result) {
                        if (result === false) {
                            return;
                        }

                        isCanceling = true;
                        document.location.href = href;
                    });

                }, this));
            }

            this.$(window).bind('beforeunload', _.bind(function () {
                if (isCanceling === false) {
                    return this.cancelMessage;
                }
            }, this));

            /** @namespace config.errorObject Error Object from CI */
            _.each(config.errorObject || {}, function (v, k) {
                var el = this.$([
                    'input[name='+k+']',
                    'textarea[name='+k+']',
                    'select[name='+k+']'
                ].join(','));

                var errorEl = $(this.errorTemplate({
                    error: v
                }));

                el.closest('.form-group')
                    .addClass('has-error').append(errorEl)
                    .on('change', function () {
                        $(this).removeClass('has-error');
                        $(errorEl).remove();
                    });

            }, this);

            this.$el.on('submit', _.bind(function () {

                if (!window.Ladda) {
                    return;
                }

                if (button[0]) {
                    var l = Ladda.create(button[0]);
                    l.start();
                }
            }, this));

            if(_.isFunction(config.ready)) {
                config.ready.call(this, error);
            }
        }
    });

})();

