AppCore = window.AppCore || {};
var ST = ST || {};


(function(){

    /**
     * Dynamic Loading base class
     *
     * @uses applib.base.form.view
     * @package app
     * @version
     * @author NOMURA Akiyuki <nomura@jamlogic.net>
     * @license
     */
    ST.dynamicLoad = AppCore.Base.extend({

        isBottom: false,
        isLoading: false,

        url: null,
        method: 'get',
        offset: 0,
        limit: 25,
        target: null,
        baseParam: {},

        /**
         * Autoload
         */
        initialize: function (config) {
            config = config || {};

            // Initialize variables
            this.url = config.url;
            this.method = config.method || 'get';
            this.offset = config.offset || 0;
            this.limit = config.limit || 25;
            this.target = config.target || null;
            this.footer = config.footer || null;
            this.baseParam = config.baseParam || {};

            // If there is no target tag
            if (!this.target || !this.target.length) {
                return;
            }

            // Set scroll handler
            $(window).scroll(_.bind(this.onScroll, this));
        },

        /**
         * Loading content
         *
         * @param config
         */
        load: function (config) {
            config = config || {};

            this.isLoading = true;

            this.trigger('beforeload', config);
            $.ajax({
                url: this.url,
                method: this.method,
                dataType : 'html',

                data: _.extend({}, this.baseParam, {
                    limit: config.limit,
                    offset: config.offset,
                    format: 'ajax'
                }),

                success: _.bind(function (res) {
                    if (!res) {
                        this.trigger('error', res);
                        return;
                    }

                    this.target.append(res);
                    this.offset += this.limit;
                    this.isLoading = false;

                    this.trigger('load', res);
                }, this),

                error: _.bind(function (res) {
                    // If there is error show error on bottom
                    this.trigger('error', res);
                }, this),

                complete: _.bind(function (res) {
                    // Hide loading indicator
                    this.trigger('complete', res);
                }, this)

            });
        },

        /**
         * run when the window is scrolled.
         */
        onScroll: function () {

            var dy = $(document).height();
            var sy = $(document).scrollTop();
            var wy = 0;
            var fy = $(this.footer).height() || 0;

            var isSafari = (navigator.appVersion.toLowerCase().indexOf('safari')+1?1:0);
            var isOpera = (navigator.userAgent.toLowerCase().indexOf('opera')+1?1:0);
            if(!isSafari && !isOpera) {
                wy = $("html").height() || $("body").height() || $(document).height();
            } else {
                wy = innerHeight;
            }

            if(this.isBottom && ((dy - fy) > (sy + wy))) {
                this.isBottom = false;
            }

            if(this.isBottom){
                return;
            }

            if(dy - fy < (sy + wy)){
                this.isBottom = true;
                this.endScroll();
            }
        },

        /**
         * When reach to the bottom of the page
         */
        endScroll: _.debounce(function () {
            if (this.isLoading) {
                return;
            }

            this.load({
                limit: this.limit,
                offset: this.offset
            });
        }, 3000, true)

    });
})();