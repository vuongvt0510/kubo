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

    STV.DrillScreen.Stage = Backbone.View.extend({
        el: '#drill-screen',
        baseUrl: '/js/drill_stage/',

        drillObject: [],
        secondObj: {},

        drillResults: [],

        currentId: 0,
        currentDrill: [],
        currentResults: [],

        modVP: null,

        tweetButtons: true,

        Drills: [],
        currentIndex: 0,
        maxIndex: 0,

        correct_number: 0,
        question_total: 0,
        currentTime: 0,
        beginDate: {},
        playDate: 0,
        trophy_index: 0,
        score_index: 0,
        good_scores: null,
        trophy_list: null,
        result_href: null,
        trophyModalEl: null,
        type_play: null,
        listQuestionId: [],
        listQuestion: [],
        secondLists: [],

        /**
         * Constructor
         *
         * @param config {Object} Config
         */
        initialize: function (config) {
            config = config || {};

            this.drillObject = config.drillObject;
            // Get list question id
            this.listQuestionId = _.pluck(this.drillObject, 'id');

            // Get list question sentence
            this.listQuestion = _.pluck(this.drillObject, 'question');

            this.maxIndex = this.drillObject.length;

            if (config.type_play) {
                this.type_play = config.type_play;
                this.redirect_retired = '/play/team/battle/room';
            } else {
                this.redirect_retired = '/play';
            }

            // load sound
            this._renderSound();

            _.each(this.drillObject, function (v) {
                // convert nl2br answer
                _.each(v.answers, function (value, key) {
                    if (value.image_url == null) {
                        value.text = value.text.replace(/\\n/g, "<br />");
                        v.answers[key].text = value.text.replace(/\\\<br \/>/g, "<br />");
                    }
                });

                // convert nl2br question
                v.question = v.question.replace(/\\n/g, "<br />");
                v.question = v.question.replace(/\\\<br \/>/g, "<br />");

                // convert {input}
                if (v.question.search("{input}")) {
                    v.question = v.question.replace(/{input}/g, "[&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;]");
                }

                // load modal
                this._launch(v, {});
            }, this);

            _.each(this.drillObject, function (v, k) {
                $('body').on('click', '.modal-questions.in .x-submit-' + v.id, _.bind(function () {
                    this.drillObject[k].timeAnswer = parseInt($('.modal-questions.in .countdown').text());
                }, this));

                $('body').on('click', '#modalDrill-' + v.id + ' input[type="radio"]', _.bind(function () {
                    this.drillObject[k].timeAnswer = parseInt($('.modal-questions.in .countdown').text());
                }, this));
            }, this);

            this.trophyModalEl = $('#modalGetTrophy').modal({
                show: false,
                backdrop: 'static'
            });

            $('#modalChangeRanking').modal({
                show: false,
                backdrop: 'static'
            });

            $('#modalHighScore').modal({
                show: false,
                backdrop: 'static'
            });

            $('#modalGetTrophy').on('click', '.close_trophy',  _.bind(function (e) {
                this.trophyModalEl.modal('hide');
            }, this));

            $('#modalGetTrophy').on('hidden.bs.modal', _.bind(function(){
                ++this.trophy_index;

                if (this.trophy_list.length > this.trophy_index) {
                    // If still trophy never show, show it
                    this.show_trophy();
                } else if (this.good_scores === null || this.good_scores.length === 0) {
                    window.location.href = this.result_href;

                } else {
                    this.show_score();
                }
            }, this));

            $(window).keydown(function(event){
                if(event.keyCode == 13) {
                    event.preventDefault();
                    return false;
                }
            });
        },

        events: {
            'click .x-btn-next': 'nextQuestionStage', // btn-next
            'click .modal-questions.in .modal-body': 'soundClick',
            'click .modal-questions.in .close': 'confirmCancel',
            'click #modalCancel-cancel': 'confirmCancelReturn',
            'click #modalDrill-cancelModal .close': 'confirmCancelReturn'
        },

        /**
         * Confirm cancel
         */
        confirmCancel: function () {
            if ($('.modal-questions.in .timer').length) {
                var sd = $('.x-sound-drill');
                sd[0].pause();

                GlobalFunctions.paused();
                $('.modal-questions.in').find('.dots-active').width($('.modal-questions.in').find('.dots-active').width());
                $('.modal-questions.in').find('.airplane').width($('.modal-questions.in').find('.airplane').width());

                $('.modal-questions.in').find('.timer').addClass('paused');
            }

            var html = $([
                '<div class="modal" id="modalDrill-cancelModal" tabindex="-1" role="dialog">',
                '<div class="modal-dialog" role="document">',
                '<div class="modal-content">',
                '<button type="button" class="close" aria-label="Close"><span aria-hidden="true">×</span></button>',
                '<div class="modal-body text-center">',
                '<p style="font-size: 16px;">リタイアしますか？</p>',
                '</div>',
                '<div class="modal-footer modal-footer-btngroup text-center">',
                '<a href="'+this.redirect_retired+'" class="btn btn-gray">リタイア</a>',
                '<button id="modalCancel-cancel" type="button" class="btn btn-green" data-dismiss="modal">続ける</button>',
                '</div>',
                '</div>',
                '</div>',
                '</div>'
            ].join(''));
            if ($('#modalDrill-cancelModal').length == 0) {
                $('#drill-screen').append(html);
            }
            $('#modalDrill-cancelModal').show().addClass('in');
        },

        confirmCancelReturn: function () {
            $('#modalDrill-cancelModal').hide().removeClass('in');

            if ($('.modal-questions.in .timer').length) {
                // play sound
                var sd = $('.x-sound-drill');
                sd[0].currentTime = 0;
                sd[0].play();

                GlobalFunctions.resume();
                $('.modal-questions.in').find('.timer').removeClass('paused');

                $('.modal-questions.in').find('.dots-active').width('100%');
                $('.modal-questions.in').find('.airplane').width('100%');
            }
        },

        /**
         * render Sound
         * @private
         */
        _renderSound: function () {
            var html = $([
                '<div><div class="x-sound-wrap">',
                '<audio class="x-sound-correct" preload="auto"><source src="/sound/correct.mp3" type="audio/mp3"></audio>',
                '<audio class="x-sound-drill" preload="auto"><source src="/sound/drill_min.mp3" type="audio/mp3"></audio>',
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
        },

        soundClick: function () {
            // play sound
            var sd = $('.x-sound-inside');
            sd[0].currentTime = 0;
            sd[0].play();
        },

        /**
         * storage drill results
         * @private
         */
        _storageDrill: function () {
            // get result by class exists
            var returnResult = ($(".modal-questions.in .modal-content").hasClass('answer-correct')) ? true : false;
            var point = $(".modal-questions.in .score .number").text();

            // close modal
            $(".modal-questions.in").modal('hide');

            // storage current results at the same time [true, false, true] ==> false. Then wrong_return_second
            this.currentResults.push(returnResult);

            // storage drill results
            var drillResultsObj = {};
            drillResultsObj.id = this.currentDrill.id;
            drillResultsObj.result = returnResult;
            drillResultsObj.speed = this.currentDrill.time_limit - this.currentDrill.timeAnswer;
            drillResultsObj.time = this.currentDrill.time_limit;
            drillResultsObj.point = parseInt(point);
            drillResultsObj.total = this.drillObject.length;

            this.drillResults.push(drillResultsObj);

            if (returnResult) {
                this.correct_number += 1;
            }

            this.currentIndex += 1;
        },

        /**
         * Next question
         */
        nextQuestionStage: function (e) {
            // disabled button
            var buttonNextQuestion = $("#modalDrill-" + this.currentDrill.id).find('.x-btn-next').prop('disabled', true);

            $("#modalDrill-" + this.currentDrill.id).append("<div class='questionAnswered'></div>");
            this.playDate += new Date() - this.beginDate;
            this.secondLists.push(new Date() - this.beginDate);

            this._storageDrill();

            if (this.Drills[this.currentIndex] != undefined) {
                this.currentDrill = this.Drills[this.currentIndex];
                this.beginDate = new Date();
                this._showModal(this.currentDrill.id, this.currentDrill.time_limit, false);

                // play sound
                var sd = $('.x-sound-drill');
                sd[0].currentTime = 0;
                sd[0].play();
            }

            // Push result
            else {
                this.showResultStage();
            }

            _.debounce(function() {}, 300);
        },

        /**
         * Show result of Drill after Play
         */
        showResultStage: function () {
            if (!_.isEmpty(this.drillResults)) {
                var scores = [];

                // Read the score
                for (var i = 0; i < this.drillResults.length; i++) {
                    scores.push(this.drillResults[i].point);
                }

                $.ajax({
                    type: 'POST',
                    url: '/play/result',
                    data: {
                        'scores': scores,
                        'question_list_ids': this.listQuestionId,
                        'question_lists': this.listQuestion,
                        'question_total': this.maxIndex,
                        'second_lists': this.secondLists,
                        'speed': this.playDate,
                        'correct_number': this.correct_number
                    },
                    dataType: 'json'
                }).done( _.bind(function (res) {
                    if (res.submit) {
                        if (res.result.trophy.length || res.result.good_score.length) {

                            if (res.result.trophy.length) {
                                this.trophy_list = res.result.trophy;
                                this.show_trophy();
                                this.result_href = res.result.href;
                            }

                            if (res.result.good_score.length) {
                                this.good_scores = res.result.good_score;
                                this.good_scores.length = 0;
 
                                // Check case high_score
                                if (this.good_scores.high_score !== null && this.good_scores.high_score !== undefined) {

                                    var rs = this.good_scores.high_score;
                                    this.good_scores.length +=1;
                                    // Add result
                                    $('.high-good-score').text(res.result.score);
                                    $('.high-score-rabipoint').text(rs.rabipoint_bonus);
                                }

                                // Check case higher in ranking
                                if (this.good_scores.higher_ranking !== null && this.good_scores.higher_ranking !== undefined) {
                                    var rs = this.good_scores.higher_ranking;
                                    this.good_scores.length +=1;
                                    // Update rabipoint
                                    $('.higher-rank-rabipoint').text(rs.rabipoint_bonus);

                                    // Update winner user
                                    $('.winner-rank').attr('data-number', rs.winner.rank);
                                    if(rs.winner.rank < 4){
                                        $('.winner-rank').addClass('top');
                                    }
                                    // Set default avatar
                                    if (rs.winner.avatar_id == 0) {
                                        rs.winner.avatar_id = 2;
                                    }
                                    $('.winner-avatar').attr('src', '/images/avatar/'+ rs.winner.avatar_id+ '.png');
                                    $('.winner-name').text(rs.winner.nickname);
                                    $('.winner-num').text(rs.winner.highest_score);

                                    // Update defeat user
                                    $('.loser-rank').attr('data-number', rs.loser.rank);
                                    if(rs.loser.rank < 4){
                                        $('.loser-rank').addClass('top');
                                    }
                                    // Set default avatar
                                    if (rs.loser.avatar_id == 0) {
                                        rs.loser.avatar_id = 2;
                                    }
                                    $('.loser-avatar').attr('src', '/images/avatar/'+ rs.loser.avatar_id+ '.png');
                                    $('.loser-name').text(rs.loser.nickname);
                                    $('.loser-num').text(rs.loser.highest_score);
                                }

                                // add default score index
                                this.score_index = 1;
                                if (this.good_scores.high_score) {
                                    this.score_index = 0;
                                }

                                // add result href
                                this.result_href = res.result.href;

                                // show high score
                                if (!res.result.trophy.length) {
                                    this.show_score();
                                }

                            }
                        } else {
                            window.location.href = res.result.href;
                        }
                    }
                }, this));
            }
        },

        /**
         * Show trophy modal
         */
        show_trophy: function () {

            var trophy = this.trophy_list[this.trophy_index];
            if (trophy) {
                $("#get_trophy_image").prop('src', '/image/show/'+trophy.image_key);
                $('#get_trophy_title').html(trophy.name);
                $('#get_trophy_description').html(trophy.description);

                this.trophyModalEl.modal('show');
            }
        },

        /**
         * Show trophy modal
         */
        show_score: function() {
            if (this.score_index == 0) {
                this.score_tag = 'modalHighScore';
            } else if (this.score_index == 1) {
                this.score_tag = 'modalChangeRanking';
            }

            // show modal
            $('#'+ this.score_tag).modal({
                show: true,
                backdrop: 'static'
            });

            $('body').on("click", '#' + this.score_tag +' .close-score', _.bind(function (e) {
                this.close_score();
            }, this));
        },

        /**
         * Close score modal
         */
        close_score: function () {
            // Hide score modal
            $('#'+ this.score_tag).modal('hide');

            // change score_index
            this.score_index += 1;

            // Consider lengh of good_score
            if (this.good_scores.length > this.score_index) {
                this.show_score();
            } else {
                window.location.href = this.result_href;
            }
        },

        /**
         * Run drill
         */
        launch: function () {
            this.checkPower();
            //show Tweet buttons when the video is playing
            if (this.tweetButtons) {
                $('.tweet-buttons').fadeIn();
            }
            this.tweetButtons = false;

            _.each(this.drillObject, function (v, k) {
                this.Drills.push(v);
            }, this);

            var Drill = this.Drills[0];

            this.currentDrill = Drill;
            this.beginDate = new Date();
            this._showModal(Drill.id, Drill.time_limit, false);
            this.speed = 0;
        },

        /**
         * Check power of user
         */
        checkPower: function () {
            if (this.drillObject) {
                $.ajax({
                    type: 'POST',
                    url: '/play/check_power',
                    data: {
                        drillObject: this.drillObject
                    },
                    dataType: 'JSON'
                }).done(function (res) {
                    if (!res.result.submit) {
                        window.location.href = "/play";
                    }
                });
            }
        },

        /**
         *
         * @param id
         * @param timeoutSecond
         * @param modalTut
         * @private
         */
        _showModal: function (id, timeoutSecond, modalTut) {

            $('.modal-questions.in').modal('hide');
            // Show up modal
            // Prevent Bootstrap Modal from disappearing when clicking outside and pressing escape
            setTimeout(_.bind(function () {
                $("#modalDrill-" + id).modal({
                    backdrop: 'static',
                    keyboard: false
                });
            }, this), 600);

            $("#modalDrill-" + id + " .modal-question-total").html('<span>' + parseInt(this.currentIndex + 1) + '問目</span>／' + this.maxIndex + '問中');

            //set question time out
            $("#modalDrill-" + id).on('shown.bs.modal', function () {
                GlobalFunctions.stop();
                GlobalFunctions.setTimer($(this).find('.timer'), timeoutSecond, function () { //timeoutSecond
                    if ($('.modal-questions.in .timer').length > 0) {
                        $("#modalDrill-" + id + " .modal-body").addClass('disable').addClass('timeout');

                        var element = $("#modalDrill-" + id).find(".questionAnswered");

                        if (element.length == 0) {
                            // stop sound
                            var sd = $('.x-sound-drill');
                            sd[0].pause();

                            // play sound
                            var sd2 = $('.x-sound-timeout');
                            sd2[0].currentTime = 0;
                            sd2[0].play();
                        }

                        // EL_STAGE1-1155 [Drill] don't show submit button at question page in case of Single answer
                        $('.x-submit-' + id).hide();
                        setTimeout(_.bind(function () {
                            $('.x-submit-' + id).click();
                        }, this), 1000 * 3);
                        // end

                    }
                });
            });
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

            //Loading class
            $.getScript(this.baseUrl + cls + '.js?' + (new Date()).getTime(), _.bind(function () {

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
        }
    });

})();
