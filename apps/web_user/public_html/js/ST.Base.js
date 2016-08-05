AppCore = window.AppCore || {};
var ST = ST || {};

(function () {
    /**
     * CPL.Base Class for all pages
     *
     * @type {void|*}
     */
    ST.Base = AppCore.Base.extend({

        el: 'body',

        events: {
            'click .x-require-login': 'showRedirectModal',
            'click .x-modal-second': 'handleMultipleModal',
            'click .x-limit-playing-video': 'limitPlayingVideo',
            'click .video-boxes .show-more-slide': 'renderListSP',
            'click .video-banner-wrapper .x-pageRefresh': 'pageRefresh'
        },


        /**
         * Constructor
         */
        initialize: function (config) {
            config = config || {};

            this.loader();
            this.setPlugin();

            // detect IE
            var IEversion = this.detectIE();
            var root = document.getElementsByTagName('html')[0];

            if (IEversion !== false) {
                //root.classList.add('ie ' + IEversion);
                //root.setAttribute( 'data-browser', 'ie' + IEversion );
                root.className += ' ie ie' + IEversion;
            }

            if (this.$el.hasClass('show-modal-lead-to-register')){
               this.showModalLeadToRegister();
            }
        },


        setPlugin: function () {

            // bootstrap material design
            // http://fezvrasta.github.io/bootstrap-material-design/
            $(window).load(function () {
                $.material.init();

                //Footer position should be at the bottom of the screen even if the contents is short
                if ($(window).width() >= 768) {
                    $('.main-contents').css("min-height", $(window).height() - $('#header').height() - $('#footer').outerHeight(true));
                }

                // showing button input password
                /*if ($('input[type="password"]').length) {
                 $('input[type="password"]').parent().prepend('<span class="toggleShowPassword"></span>');
                 }*/

                if ($('.togglePassword').length) {
                    $('.togglePassword').parent().prepend('<span class="toggleShowPassword"></span>');
                }

                $('body').on("click", '.toggleShowPassword', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var inputType = $(this).parent().find('input');
                    if (inputType.attr('type') == 'password') {
                        inputType.attr('type', 'text');
                    }
                    else {
                        inputType.attr('type', 'password');
                    }
                });

                $('.avatar-radio input[type="radio"]').on('click', function () {
                    $('.avatar-radio img').show();
                    $('.avatar-radio img.selected').hide();
                    if ($(this).is(':checked')) {
                        $(this).parents('.avatar-radio').find('img').hide();
                        $(this).parents('.avatar-radio').find('img.selected').show();
                    }
                });

            });

            Modernizr.addTest('isios', function () {
                return navigator.userAgent.match(/(iPad|iPhone|iPod)/g) ? true : false;
            });

            // Placeholders
            // https://github.com/mathiasbynens/jquery-placeholder
            $('input, textarea').placeholder();

            // carousel (owl slider js)
            if ($('.x-owl-slider').length) {
                $('.x-owl-slider').owlCarousel({
                    items: 4,
                    pagination: false,
                    navigation: true,
                    navigationText: ["<i class='el-icons el-angle-left'></i>", "<i class='el-icons el-angle-right'></i>"]
                });
            }

            // carousel (owl slider js)
            if ($('.x-trophy-list').length) {
                $('.x-trophy-list').owlCarousel({
                    items: 6,
                    pagination: false,
                    navigation: true,
                    navigationText: ["<i class='el-icons el-angle-left'></i>", "<i class='el-icons el-angle-right'></i>"]
                });
            }

            // crousel only for DK10 SP
            $('.x-slick').slick({
                slidesToShow: 1,
                slidesToScroll: 1,
                centerMode: true,
                focusOnSelect: true,
                arrows: false,
                variableWidth: true
            });

            // ellipsis text (dotdotdot js)
            if ($('.x-ellipsis').length) {
                $('.x-ellipsis').dotdotdot({
                    watch: true,
                    wrap: 'letter'
                });
            }


        },

        /**
         * show message modal before Redirect
         */
        showRedirectModal: function (e, config) {
            config = config || {};
            var self = this;

            var message = $(e.currentTarget).data('message');
            var label = $(e.currentTarget).data('label');
            var url = $(e.currentTarget).data('url');

            self.dialog({
                message: message,
                buttons: {
                    cancel: {
                        label: "キャンセル",
                        className: "btn-gray"
                    },
                    ok: {
                        label: label,
                        className: "btn-green",
                        callback: function () {
                            location.href = url;
                        }
                    }
                }
            });
        },

        /**
         * handle second modal (bootstrap)
         * when show next, hide current
         */
        handleMultipleModal: function (e) {
            var checked_click = 1,
                current = $(e.currentTarget);
            current.closest('.modal').modal('hide').on('hidden.bs.modal', function () {
                if (checked_click == 1) {
                    $(current.attr('href')).modal('show');
                }
                checked_click = 0;
            });
        },

        /**
         * lead to register page after non login user watch 2 videos
         */
        limitPlayingVideo: function (e, config) {

            e.preventDefault();

            var val = this.getCookie('STV_time_out').split(':');
            if (val.length > 2) {
                $("#modalLeadToRegister").modal('show');
            } else {
                window.location.href = $(e.currentTarget).prop('href');
                return;
            }
        },

        showModalLeadToRegister: function () {
            var val = this.getCookie('STV_time_out').split(':');
            if (val.length > 2) {
                $("#modalLeadToRegister").modal('show');
            }
        },

        /**
         * Get video cookie
         */
        getCookie: function (name) {
            var name2 = name + "=";
            var ca = document.cookie.split(';');
            for (var i = 0; i < ca.length; i++) {
                var c = ca[i];
                while (c.charAt(0) == ' ') c = c.substring(1);
                if (c.indexOf(name2) === 0) return c.substring(name2.length, c.length);
            }
            return "";
        },


        /**
         * show hidden Items of list (currently, for video list and textbook list)
         */
        renderListSP: function (e) {
            e.preventDefault();
            $(e.currentTarget).addClass('hidden');
            $(e.currentTarget).closest('.video-boxes').find('.item.hidden').removeClass('hidden');
        },

        /**
         * show hidden Items of list (currently, for video list and textbook list)
         */
        pageRefresh: function (e) {
            e.stopPropagation();
            e.preventDefault();
            location.reload();
        },

        detectIE: function () {
            var ua = window.navigator.userAgent;

            // Test values; Uncomment to check result …

            // IE 10
            // ua = 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.2; Trident/6.0)';

            // IE 11
            // ua = 'Mozilla/5.0 (Windows NT 6.3; Trident/7.0; rv:11.0) like Gecko';

            // IE 12 / Spartan
            // ua = 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/39.0.2171.71 Safari/537.36 Edge/12.0';

            // Edge (IE 12+)
            // ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/46.0.2486.0 Safari/537.36 Edge/13.10586';

            var msie = ua.indexOf('MSIE ');
            if (msie > 0) {
                // IE 10 or older => return version number
                return parseInt(ua.substring(msie + 5, ua.indexOf('.', msie)), 10);
            }

            var trident = ua.indexOf('Trident/');
            if (trident > 0) {
                // IE 11 => return version number
                var rv = ua.indexOf('rv:');
                return parseInt(ua.substring(rv + 3, ua.indexOf('.', rv)), 10);
            }

            var edge = ua.indexOf('Edge/');
            if (edge > 0) {
                // Edge (IE 12+) => return version number
                return parseInt(ua.substring(edge + 5, ua.indexOf('.', edge)), 10);
            }

            // other browser
            return false;
        }

    });
})();
