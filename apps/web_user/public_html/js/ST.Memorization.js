var ST = ST || {};

(function(){

    "use strict";

    ST.Memorization = Backbone.View.extend({

        memorizations : {},
        memorization_index : 0,
        memorization_id : null,
        user_id : null,
        remove_question : false,
        start_time : 'first',

        /**
         * Autoload
         */
        initialize: function (config) {

            this.memorizations = config.memorizations;
            this.user_id = config.user_id;
            this.remove_question = config.remove_question;

            $("#memorize-next").click(_.bind(this.nextMemorization, this));
            $("#memorize-previous").click(_.bind(this.previousMemorization, this));
            $("#memorize-circle").click(_.bind(this.changeStatus_circle, this));
            $("#memorize-triangle").click(_.bind(this.changeStatus_triangle, this));
            $("#memorize-cross").click(_.bind(this.changeStatus_cross, this));
            $("#memorize-result").click(_.bind(this.memorizeResult, this));
            $(".vocab-sound").click(_.bind(this.playSound_vocab, this));
        },

        nextMemorization: function() {

            if (this.remove_question == true && this.memorizations[this.memorization_index].status == 'remember') {
                this.memorizations.splice(this.memorization_index, 1);

                if (this.memorizations.length - 1 <= this.memorization_index) {
                    this.memorization_index = 0;
                }
            } else {

                if (this.memorizations.length - 1 <= this.memorization_index) {
                    this.memorization_index = 0;
                } else {
                    this.memorization_index += 1;
                }
            }
            this.change_memorization();

        },

        previousMemorization: function() {

            if (this.remove_question == true && this.memorizations[this.memorization_index].status == 'remember') {
                this.memorizations.splice(this.memorization_index, 1);
                if (this.memorization_index == 0) {
                    this.memorization_index = this.memorizations.length - 1;
                }
            } else {

                if (this.memorization_index == 0) {
                    this.memorization_index = this.memorizations.length - 1;
                } else {
                    this.memorization_index -= 1;
                }
            }

            this.change_memorization();
        },

        changeStatus_circle: function() {
            this.change_status(this.memorization_id, 'remember');

            // active selected button (not done yet)

        },

        changeStatus_triangle: function() {
            this.change_status(this.memorization_id, 'consider');

            // active selected button (not done yet)
        },

        changeStatus_cross: function() {
            this.change_status(this.memorization_id, 'forget');

            // active selected button (not done yet)
        },

        memorizeResult: function() {

            var end = Date.now();
            var total_second = this.start_time != 'first' ? parseInt((end - this.start_time)/1000) : 0;

            $.ajax({
                method: "POST",
                url: "/play/get_memorize_result",
                data: {
                    total_second: total_second
                },
                dataType: 'json'
            }).done( function (res) {
                $('#remember_result').html(res.memorization.remember);
                $('#consider_result').html(res.memorization.consider);
                $('#forget_result').html(res.memorization.forget);
                $('#not_checked_result').html(res.memorization.not_checked);

                $('#memorize_text1').html(res.text1);
                $('#memorize_text2').html(res.text2);
            });

            $("#modalMemorization").modal({
                keyboard: false,
                backdrop: 'static'
            });
        },

        change_memorization: function() {

            $("#areaAnswer").removeClass('flipped');
            var memorization = this.memorizations[this.memorization_index];

            $('#memorize-key').html(memorization.question);

            switch (memorization.status) {
                case 'remember':
                    $("#memorize-triangle").removeClass('active');
                    $("#memorize-cross").removeClass('active');
                    $("#memorize-circle").addClass('active');
                    break;

                case 'consider':
                    $("#memorize-triangle").addClass('active');
                    $("#memorize-cross").removeClass('active');
                    $("#memorize-circle").removeClass('active');
                    break;

                case 'forget':
                    $("#memorize-triangle").removeClass('active');
                    $("#memorize-cross").addClass('active');
                    $("#memorize-circle").removeClass('active');
                    break;

                default:
                    $("#memorize-circle").removeClass('active');
                    $("#memorize-cross").removeClass('active');
                    $("#memorize-triangle").removeClass('active');
            }

            this.memorization_id = this.memorizations[this.memorization_index].id;

            if (this.memorizations.length == 1) {
                $("#memorize-next").css('opacity', '0.6');
                $("#memorize-previous").css('opacity', '0.6');
            }

            setTimeout(
                function()
                {
                    $('#memorize-answer').html(memorization.answer);
                }, 700);
        },

        change_status: function(memorization_id, status) {

            if (this.start_time == 'first') {
                var start = Date.now();
                this.start_time = start;
            }

            $.ajax({
                method: "POST",
                url: "/play/change_memorization",
                data: {
                    user_id: this.user_id,
                    memorization_id: memorization_id,
                    status: status
                },
                dataType: 'json'
            });

            var image = null;
            var text = null;
            var name = null;
            this.memorizations[this.memorization_index].status = status;
            switch (status) {
                case 'remember': image = 'remember.png';
                    text = 'やったぁ！';
                    name = '覚えた！';
                    var audio = new Audio('/sound/memorize_circle.mp3');
                    $("#memorize-triangle").removeClass('active');
                    $("#memorize-cross").removeClass('active');
                    $("#memorize-circle").addClass('active');
                    break;

                case 'consider': image = 'more.png';
                    text = 'できるできる！';
                    name = 'もう少し';
                    var audio = new Audio('/sound/memorize_triangle.mp3');
                    $("#memorize-circle").removeClass('active');
                    $("#memorize-cross").removeClass('active');
                    $("#memorize-triangle").addClass('active');
                    break;

                case 'forget': image = 'no-remember.png';
                    text = 'ラビとがんばろう！';
                    name = '覚えていない';
                    var audio = new Audio('/sound/memorize_cross.mp3');
                    $("#memorize-circle").removeClass('active');
                    $("#memorize-triangle").removeClass('active');
                    $("#memorize-cross").addClass('active');
                    break;
            }

            if (status != 'not_checked') {
                audio.play();
                $("#modalMemorizeStatus_image").prop('src', '/images/play/'+image);

                $('#modalMemorizeStatus_text').html(text);

                $('#modalMemorizeStatus_name').html(name);

                $("#modalMemorizeStatus").modal('show');
                setTimeout(function(){
                    $("#modalMemorizeStatus").modal('hide');
                }, 1000);
            }
        },

        playSound_vocab: function () {
            // play sound
            var audio = new Audio('/file/get/'+this.memorizations[this.memorization_index].sound_key);
            audio.play();
        }
    });
})();
