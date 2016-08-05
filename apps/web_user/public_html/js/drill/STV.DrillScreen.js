var STV = STV || {};
var GlobalFunctions = function () {
    var isPaused = false;
    var interval;
    return {
        paused: function () {
            return isPaused = true;
        },
        resume: function () {
            return isPaused = false;
        },
        stop: function () {
            clearInterval(interval);
        },
        setTimer: function ($element, timeoutSecond, callback) {
            $element.addClass('active');
            var timesRun = timeoutSecond;

            $element.find('.countdown').hide().html(timesRun);
            /*Animation for item*/
            $element.find('.dots-active, .airplane').css({
                'transition': 'width ' + timesRun + 's linear',
                '-webkit-transition': 'width ' + timesRun + 's linear',
                'width': '100%'
            });
            $element.find('.time-out-text').css({
                'transition': 'color 0s linear ' + timeoutSecond + 's',
                '-webkit-transition': 'color 0s linear ' + timeoutSecond + 's',
            }).addClass('active');
            /*end Animation for item*/

            interval = setInterval(function () {
                if (!isPaused) {
                    timesRun -= 1;
                    if (timesRun === 0) {
                        $element.addClass('end-timeout-section').removeClass('in-timeout-section');
                        if (callback && typeof callback == 'function') {
                            callback();
                        }
                        clearInterval(interval);
                    }

                    $element.find('.countdown').html(timesRun);
                    /*Animation for background*/
                    if (timesRun === Math.ceil(timeoutSecond / 3 * 2)) {
                        $element.addClass('in-warning-section');
                    }
                    if (timesRun === Math.ceil(timeoutSecond / 3)) {
                        $element.addClass('in-timeout-section');
                    }
                    /*end Animation for background*/

                    /*Show countdown*/
                    if (timesRun >= 0 && timesRun <= 5) {
                        $element.find('.countdown').show();
                    } else {
                        $element.find('.countdown').hide();
                    }
                    /*end Show countdown*/

                    /*Animation for item*/
                    $element.find('.dots-active, .airplane').css({
                        'transition': 'width ' + timesRun + 's linear',
                        '-webkit-transition': 'width ' + timesRun + 's linear',
                        'width': '100%'
                    });
                    $element.find('.time-out-text').css({
                        'transition': 'color 0s linear ' + timeoutSecond + 's',
                        '-webkit-transition': 'color 0s linear ' + timeoutSecond + 's',
                    }).addClass('active');
                    /*end Animation for item*/

                }
            }, 1000);
        }
    }
}();

/**
 * Drill Base Screen
 *
 * @type {*|void|Object}
 */
(function () {
    "use strict";

    STV.DrillScreen = Backbone.View.extend({
        el: '#drill-screen',
        baseUrl: '/js/drill',

        drillObject: [],
        secondObj: {},

        drillResults: [],

        currentId: 0,
        currentDrill: [],
        currentResults: [],

        modVP: null,

        tweetButtons: true,

        /**
         * Constructor
         *
         * @param config {Object} Config
         */
        initialize: function (config) {
            config = config || {};

            this.drillObject = config.drillObject;
            this.secondObj = _.groupBy(this.drillObject, 'second');

            // load sound
            this._renderSound();

            _.each(this.drillObject, function (v) {
                // convert nl2br answer
                _.each(v.answers, function (value, key) {
                    if (value.image_url == null) {
                        value.text = value.text.replace(/\\n/g, "<br />");
                        v.answers[key].text = value.text.replace(/\\\<br \/>/g, "<br />");
                    }

                    // convert [sup]
                    if (value.text) {
                        v.answers[key].text = value.text.replace(/\[sup\]/g, "<sup>");
                        v.answers[key].text = value.text.replace(/\[\/sup\]/g, "</sup>");
                    }
                });

                // convert nl2br question
                v.question = v.question.replace(/\\n/g, "<br />");
                v.question = v.question.replace(/\\\<br \/>/g, "<br />");

                // convert {input}
                if (v.question.search("{input}")) {
                    v.question = v.question.replace(/{input}/g, "[&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;]");
                }

                // convert [sup]
                v.question = v.question.replace(/\[sup\]/g, "<sup>");
                v.question = v.question.replace(/\[\/sup\]/g, "</sup>");


                // load modal
                this._launch(v, {});
            }, this);

            $(window).keydown(function(event){
                if(event.keyCode == 13) {
                    event.preventDefault();
                    return false;
                }
            });

        },

        events: {
            'click .x-btn-prev': 'returnQuestion',
            'click .x-btn-next': 'returnPlayVideo',
            'click .modal-questions .close': 'returnPlayVideo', // current result is false
            'click .modal-questions.in .modal-body': 'soundClick',
            'click #modalDrill-tut .btn': 'soundClickStart'
        },

        /**
         * render Sound
         * @private
         */
        _renderSound: function () {
            var html = $([
                '<div><div class="x-sound-wrap">',
                '<audio class="x-sound-correct" preload="auto"><source src="/sound/correct.mp3" type="audio/mp3"></audio>',
                '<audio id="audio_2" class="x-sound-drill" preload="auto"><source src="/sound/drill_min.mp3" type="audio/mp3"></audio>',
                '<audio class="x-sound-inside" preload="auto"><source src="/sound/inside_the_drill.mp3" type="audio/mp3"></audio>',
                '<audio class="x-sound-ranking" preload="auto"><source src="/sound/ranking_modal.mp3" type="audio/mp3"></audio>',
                '<audio class="x-sound-tutorial" preload="auto"><source src="/sound/tutorial_modal.mp3" type="audio/mp3"></audio>',
                '<audio class="x-sound-wrong" preload="auto"><source src="/sound/wrong.mp3" type="audio/mp3"></audio>',
                '<audio class="x-sound-timeout" preload="auto"><source src="/sound/timeout.mp3" type="audio/mp3"></audio>',
                '</div></div>'
            ].join(''));
            if ($('.x-sound-wrap').length == 0) {
                $('body').append(html);
            }
            /* play sound
             this.drillSound.drill[0].currentTime = 0;
             this.drillSound.drill[0].play();*/
        },

        soundClick: function () {
            // play sound
            var sd = $('.x-sound-inside');
            sd[0].currentTime = 0;
            sd[0].play();
        },

        soundClickStart: function () {
            if (Modernizr.touch || Modernizr.isios) {
                // play sound
                var sd = $('.x-sound-drill');
                sd[0].currentTime = 0;
                sd[0].play();
            }
        },

        /**
         * storage drill results
         * @private
         */
        _storageDrill: function () {
            // get result by class exists
            var returnResult = ($(".modal-questions.in .modal-content").hasClass('answer-correct')) ? true : false;
            var point = $(".modal-questions.in .score .number").text();
            var timeAnswer = $(".modal-questions.in .score .second").text();

            // close modal
            $(".modal-questions.in").modal('hide');

            // storage current results at the same time [true, false, true] ==> false. Then wrong_return_second
            this.currentResults.push(returnResult);

            // storage drill results
            var drillResultsObj = {};
            drillResultsObj.id = this.currentId;
            drillResultsObj.result = returnResult;
            drillResultsObj.point = point;
            drillResultsObj.second = timeAnswer;
            drillResultsObj.total = this.drillObject.length;
            this.drillResults.push(drillResultsObj);

            /*
             if (this.drillResults.length == this.drillObject.length) {
             console.log(this.drillResults);
             }*/

        },


        /**
         * return to previous question
         */
        returnQuestion: function () {
            this._storageDrill();
            this.modVP.currentTime(this.currentDrill[0].wrong_return_second);
            this.modVP.play();
        },

        /**
         * Continue video after user answered
         */
        returnPlayVideo: function () {
            this._storageDrill();

            // checking still have questions
            if (this.currentDrill.length > 1) {

                this.currentDrill = _.rest(this.currentDrill);
                this.currentId = this.currentDrill[0].id;
                this._showModal(this.currentDrill[0].id, this.currentDrill[0].time_limit);

            } else {

                //check current object
                _.each(this.currentDrill, function (v) {
                    if (this.currentId != v.id) {
                        return;
                    }

                    /* return wrong second if user answered is wrong
                     // Correct
                     if (_.contains(this.currentResults, false) === false) {
                     this.modVP.currentTime(v.correct_return_second);
                     this.modVP.play();
                     } else {
                     this.modVP.currentTime(v.wrong_return_second);
                     this.modVP.play();
                     }*/

                    // continue play video when correct_return_second is null
                    if (v.correct_return_second != null) {
                        this.modVP.currentTime(v.correct_return_second);
                    }
                    this.modVP.play();

                }, this);

            }

            // stop sound
            var sd = $('.x-sound-drill');
            sd[0].pause();

        },

        /**
         *
         * @param second
         * @param modVP
         */
        launch: function (second, modVP) {
            _.each(this.secondObj, function (v, k) {
                if (second < parseFloat(k) + 0.01 || second > (parseFloat(k) + 1.0)) {
                    return false;
                }
                this.currentDrill = v;

                this.modVP = modVP;
                this.currentId = v[0].id;

                // the question has been answered
                // not yet
                if (this.drillResults.length == 0) {
                    // show modal tutorial
                    // show modal drill
                    this._showModal(v[0].id, v[0].time_limit, true);
                } else {
                    var y = _.some(this.drillResults, function (c) {
                        return c.id == this.currentId;
                    }, this);
                    if (y == false) {
                        this._showModal(v[0].id, v[0].time_limit);
                    }
                }

            }, this);
        },

        /**
         *
         * @param id
         * @param timeoutSecond
         * @param modalTut
         * @private
         */
        _showModal: function (id, timeoutSecond, modalTut) {

            if (Modernizr.touch || Modernizr.isios) {
                $("#myExperience video")[0].webkitExitFullScreen();
            }

            // Show up modal
            // Prevent Bootstrap Modal from disappearing when clicking outside and pressing escape
            setTimeout(_.bind(function () {
                if (modalTut) {
                    $("#modalDrill-tut").modal('show');

                    // play sound
                    var sd = $('.x-sound-tutorial');
                    sd[0].currentTime = 0;
                    sd[0].play();

                } else {
                    $("#modalDrill-" + id).modal({
                        backdrop: 'static',
                        keyboard: false
                    });

                    // play sound
                    var sd = $('.x-sound-drill');
                    sd[0].pause();
                    sd[0].currentTime = 0;
                    sd[0].play();

                }

            }, this), 600);

            $("#modalDrill-tut").on('hidden.bs.modal', function () {
                $("#modalDrill-" + id).modal({
                    backdrop: 'static',
                    keyboard: false
                });
                // play sound
                var sd = $('.x-sound-drill');
                sd[0].pause();
                sd[0].currentTime = 0;
                sd[0].play();
            });


            function _countDownNum(number) {
                if (number > 0) {
                    number -= 1;
                    window.setTimeout(function () {
                        _countDownNum(number);
                    }, 1000);
                }
                return number;
            };

            //set question time out
            $("#modalDrill-" + id).on('shown.bs.modal', function () {
                GlobalFunctions.stop();
                GlobalFunctions.setTimer($(this).find('.timer'), timeoutSecond, function () {
                    if ($('.modal-questions.in .timer').length > 0) {
                        $("#modalDrill-" + id + " .modal-body").addClass('disable').addClass('timeout');

                        // stop sound
                        var sd = $('.x-sound-drill');
                        sd[0].pause();

                        // play sound
                        var sd2 = $('.x-sound-timeout');
                        sd2[0].currentTime = 0;
                        sd2[0].play();

                        // EL_STAGE1-1155 [Drill] don't show submit button at question page in case of Single answer
                        $('.x-submit-' + id).hide();
                        setTimeout(_.bind(function () {
                            $('.x-submit-' + id).click();
                        }, this), 1000 * 3);
                        // end


                    }
                });
            });

            // pause video
            this.modVP.pause(true);
        },


        /**
         * Launch Drill Screen
         *
         * @param {Object} drillObject Drill Object
         * @param {Object} config
         */
        _launch: function (drillObject, config) {
            var type = drillObject.type || null;
            var cls = 'STV.DrillScreen.' + this.ucfirst(type);

            if (!type) {
                return;
            }

            // Loading class
            $.getScript(this.baseUrl + '/' + cls + '.js?' + (new Date()).getTime(), _.bind(function () {

                var obj = new (eval(cls))({ // jshint ignore:line
                    drillObject: drillObject
                });
                obj.open();
            }));
        },

        /**
         *
         * @param {string} string
         * @returns {string}
         */
        ucfirst: function (string) {
            return string.charAt(0).toUpperCase() + string.slice(1);
        },

        /**
         *
         * @param {int} video_id
         * @returns {string}
         */
        show_ranking: function (video_id) {
            $.ajax({
                type: 'GET',
                url: '/question/get_ranking',
                data: {
                    'target_id': video_id,
                },
                dataType: 'html'
            }).done(function (data) {
                // Remove old model
                $('#modalDrill-ranking').remove();
                $('body').append(data);

                // Register form event
                $('.ranking-input-name').click(function () {

                    if ($('.ranking-nickname').val().trim() == '' || $('.ranking-nickname').val().trim() == null) {
                        $('.help-text').show();
                        return false;
                    }

                    // Set the name for ranking
                    $('.show-ranking-name').text($('.ranking-nickname').val());

                    $('.input-group').hide();
                    $('.help-text').hide();
                    $('.ranking-register').show();
                });

                // Get the rabi count
                //EL_STAGE1-1627 ラビボタンのデータの持ち方と表示のされ方 | Spec changes in tweet function
                /*$.ajax({
                 url: '/video/get_rabbit_count',
                 method: 'post',
                 data: {'video_id': video_id},
                 success: function (data) {
                 if (Object.keys(data).length > 0) {
                 data = data['items'];

                 for (var i in data) {
                 $('.rabi-' + data[i]['button_id']).parent('li').find('.count').html(data[i]['count']);
                 }
                 }
                 }
                 });*/
                var rabi_count = $('#drill-screen .tweet-buttons .list-unstyled').html();
                $('#modalDrill-ranking .tweet-buttons-horizontal .list-unstyled').html(rabi_count);
                //end EL_STAGE1-1627 ラビボタンのデータの持ち方と表示のされ方 | Spec changes in tweet function


                // Show model
                if (Modernizr.touch || Modernizr.isios) {
                    $("#myExperience video")[0].webkitExitFullScreen();
                }
                $('#modalDrill-ranking').modal('show');
            });

        },
        /**
         * Create the total score
         */
        create_score: function (video_id) {
            var current_target = this;
            if (!_.isEmpty(this.drillResults)) {

                var scores = [];
                var total = 0;

                // Read the score
                for (var i = 0; i < this.drillResults.length; i++) {
                    if (this.drillResults[i].point != 0) {
                        scores.push(this.drillResults[i].point);
                        total = this.drillResults[i].total;
                    }
                }

                $.ajax({
                    type: 'POST',
                    url: '/question/create_score',
                    data: {
                        'scores': scores,
                        'total': total,
                        'target_id': video_id
                    },
                    dataType: 'json'
                }).done(function (data) {
                    // Show ranking
                    current_target.show_ranking(video_id);
                });
            } else {
                current_target.show_ranking(video_id);
            }
        }

    });

})();
