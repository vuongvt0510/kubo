

$(window).ready(function () {
    var now = new Date(BUSSINESS_TIME.replace(' ','T'));

    // Auto update common time
    var update_common_time = function () {

        now.setSeconds(now.getSeconds() + 1);

        $('.common-time').each(function () {

            var self = $(this);
            var timeString = self.data('time');

            var datetime = new Date(timeString.replace(' ','T'));

            var time_space = parseInt((now.getTime() - datetime.getTime()) / 1000);

            var common_time = '';

            if (time_space < 60) {
                common_time =  "1 分前";
            } else if (60 <= time_space && time_space < 3600) {
                common_time = parseInt(time_space / 60 ) + " 分前";
            } else if (3600 <= time_space && time_space < 86400) {
                common_time = parseInt(time_space / 3600) + " 時間前";
            } else if (86400 < time_space) {
                var month = datetime.getMonth() + 1;
                var date = datetime.getUTCDate();
                var year = datetime.getFullYear();

                if (month < 10) {
                    month = '0' + month;
                }

                if (date < 10) {
                    date = '0' + date;
                }

                common_time = year + "-" + month + "-" + date;

                // If space time is large, just remove event from this dom
                self.toggleClass('common-time');
            }

            if (self.text() != common_time) {
                self.html(common_time);
            }
        });

    }

    setInterval(update_common_time, 1000);

    var is_on_progress_ajax_notification_and_message = false;
    /**
     * Check notification function
     */
    var check_new_notification_and_message = function () {

        if (is_on_progress_ajax_notification_and_message) {
            return;
        }

        is_on_progress_ajax_notification_and_message = true;

        $.ajax({
            url: '/notification/notification_check',
            success: function(res) {
                is_on_progress_ajax_notification_and_message = false;
                $('.x-badge_message').html(res.result.message ? res.result.message : '' );
                $('.x-badge_notification').html(res.result.notification ? res.result.notification : '');
            },
            error: function () {
                is_on_progress_ajax_notification_and_message = false;
            }
        });
    }

    if (IS_LOGGED_IN) {
        // Check is on render page
        check_new_notification_and_message();

        setInterval(check_new_notification_and_message, 5000);
    }
});
