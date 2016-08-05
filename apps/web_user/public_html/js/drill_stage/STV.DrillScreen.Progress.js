var STV = STV || {};

/**
 * Drill Update video progress
 *
 * @type {*|void|Object}
 */
(function () {
    "use strict";

    STV.DrillScreen.Progress = STV.DrillScreen.Base.extend({

        timeToUpdate : 5,
        videoID : 0,
        cookieFlag : false,
        cookieName: 'user_video_cookie',

        session: null,
        isDone: false,

        /**
         * Constructor
         *
         * @param config {Object} Config
         */
        initialize: function (config) {
            config = config || {};
            this.videoID = config.videoID || 0;
            this.cookieFlag = config.cookieFlag || false;
            this.session = Math.random().toString(36).substring(7);
        },

        /**
         * Update video progress
         */
        update: function (currentTime, doneFlag, videoLength) {

            if (this.isDone) {
                return;
            }

            var session = this.session;
            var cookie = $.cookie(this.cookieName);

            this.isDone = doneFlag;

            // Add cookie
            if (!cookie) {
                $.cookie(this.cookieName, Math.random().toString(36), { expires: 30 * 24, path: '/'});
                cookie = $.cookie(this.cookieName);
            }

            if ((currentTime > timeToUpdate + 5 && videoLength > 0 ) || doneFlag == 1) {
                timeToUpdate = currentTime;

                $.ajax({
                    type: "POST",
                    url: '/video/update_progress',
                    data: {
                        'second' : currentTime,
                        'session_id' : session,
                        'cookie_id' : cookie,
                        'video_id' : this.videoID,
                        'done_flag' : doneFlag,
                        'duration' : videoLength
                    },
                    dataType: 'json'
                });

                // Update cookie time views
                if(this.cookieFlag === true && currentTime > 30 ) {
                    var val = this.getCookie('STV_time_out').split(':');

                    if(!_.contains(val, this.videoID)) {
                        if(val == "") {
                            val[0] = this.videoID;
                        } else {
                            val.push(this.videoID);
                        }
                        var join = val.join(':');
                        this.updateCookie('STV_time_out', join);
                    }

                    if(val.length > 2) {
                        window.location.href = '/register';
                        return;
                    }
                }
            }
        },

        /**
         * Update video progress style
         */
        updateProgress: function (currentLength, videoLength, el) {
            $(el).attr('style','width:' + (currentLength * 100 /videoLength) + '%' );
            $(el).attr('aria-valuenow', (currentLength * 100 /videoLength) );
        },

        /**
         * Update video cookie
         */
        updateCookie: function (name, value) {
            var today = new Date(),
                offset = (typeof time == 'undefined') ? (1000 * 60 * 60 * 24) : (time * 1000),
                expires_at = new Date(today.getTime() + offset);

            var cookie = _.map({
                name: value,
                expires: expires_at.toGMTString(),
                path: '/'
            }, function(value, key) {
                return [(key == 'name') ? name : key, value].join('=');
            }).join(';');

            document.cookie = cookie;
        },

        /**
         * Get video cookie
         */
        getCookie: function (name) {
            var name = name + "=";
            var ca = document.cookie.split(';');
            for(var i=0; i < ca.length; i++) {
                var c = ca[i];
                while (c.charAt(0)==' ') c = c.substring(1);
                if (c.indexOf(name) == 0) return c.substring(name.length,c.length);
            }
            return "";
        },
        /**
         * Update video view count
         */
        update_view_count: function () {
            $.ajax({
                type: "POST",
                url: '/video/update_video_count',
                data: {
                    'video_id' : this.videoID
                },
                dataType: 'json'
            })
        }

    });

})();
