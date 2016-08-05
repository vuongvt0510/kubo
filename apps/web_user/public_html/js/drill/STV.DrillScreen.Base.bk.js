var STV = STV || {};

/**
 * Drill Single Answer
 *
 * @type {*|void|Object}
 */
(function () {
    "use strict";

    STV.DrillScreen.Base = Backbone.View.extend({

        /**
         * Basic screen display
         */
        render: function () {
        },

        /**
         * Show Answer to screen
         *
         * @param {object} config
         */
        showAnswerCorrect: function (config) {

            var dataCorrect = null;
            var html = '<div class="questions-dataCorrect">正解：';
            if (config.drillType != undefined) {
                switch (config.drillType) {
                    case 'single':
                    {
                        if(config.drillObject.answers[0].image_url == null){
                            dataCorrect = _.pluck(_.where(config.drillObject.answers, {correct: true}), 'text');
                            _.each(dataCorrect, function (v) {
                                html += v;
                            });
                        } else {
                            dataCorrect = _.pluck(_.where(config.drillObject.answers, {correct: true}), 'image_url');
                            _.each(dataCorrect, function (v) {
                                html += '<img width="150" src="/image/show/' + v + '">';
                            });
                        }

                    }
                        break;

                    case 'text':
                    {
                        dataCorrect = _.pluck(config.drillObject.answers, 'correct');
                        _.each(dataCorrect, function (v) {
                            html += v;
                        });
                    }
                        break;

                    case 'multiple':
                    {
                        if(config.drillObject.answers[0].image_url == null){
                            dataCorrect = _.pluck(_.where(config.drillObject.answers, {correct: true}), 'text');
                            _.each(dataCorrect, function (v, k) {
                                html += v;
                                if (k+1 < dataCorrect.length){
                                    html += '／';
                                }
                            });
                        } else {
                            dataCorrect = _.pluck(_.where(config.drillObject.answers, {correct: true}), 'image_url');
                            _.each(dataCorrect, function (v, k) {
                                html += '<img width="150" src="/image/show/' + v + '">';
                                if (k+1 < dataCorrect.length){
                                    html += '／';
                                }
                            });
                        }
                    }
                        break;

                    case 'multi_text':
                    {
                        dataCorrect = _.pluck(config.drillObject.answers, 'correct');
                        _.each(dataCorrect, function (v, k) {
                            html += v;
                            if (k+1 < dataCorrect.length){
                                html += '／';
                            }
                        });
                    }
                        break;

                    case 'multi_field':
                    {
                        if(config.drillObject.answers[0].image_url == null){
                            dataCorrect = _.pluck(_.where(config.drillObject.answers, {correct: true}), 'text');
                            _.each(dataCorrect, function (v, k) {
                                html += v;
                                if (k+1 < dataCorrect.length){
                                    html += '／';
                                }
                            });
                        } else {
                            dataCorrect = _.pluck(_.where(config.drillObject.answers, {correct: true}), 'image_url');
                            _.each(dataCorrect, function (v, k) {
                                html += '<img width="150" src="/image/show/' + v + '">';
                                if (k+1 < dataCorrect.length){
                                    html += '／';
                                }
                            });
                        }
                    }
                        break;

                    case 'group':
                    {
                        var dataCorrect_temp = _.values(_.groupBy(config.drillObject.answers, 'correct'));
                        html += '<div class="row mt20">';

                        if(config.drillObject.answers[0].image_url == null){
                            _.each(dataCorrect_temp, function (v, k) {
                                html += '<div class="col-sm-6">'
                                _.each(v, function (vl, ke) {
                                    html += vl.text;
                                    if (ke+1 < v.length){
                                        html += '／';
                                    }
                                });
                                html += '</div>'
                            });
                        } else {
                            _.each(dataCorrect_temp, function (v, k) {
                                html += '<div class="col-sm-6">'
                                _.each(v, function (vl, ke) {
                                    html += '<img width="50" src="/image/show/' + vl.image_url + '">';
                                    if (ke+1 < v.length){
                                        html += '／';
                                    }
                                });
                                html += '</div>'
                            });
                        }
                        html += '</div>'
                    }
                        break;

                    case 'sort':
                    {
                        var dataCorrect_temp = _.sortBy(config.drillObject.answers, 'correct');
                        dataCorrect = _.pluck(dataCorrect_temp, 'text');
                        _.each(dataCorrect, function (v, k) {
                            html += v;
                            if (k+1 < dataCorrect.length){
                                html += '／';
                            }
                        });
                    }
                        break;

                    default:
                    {
                        //console.log(result,config.drillType);
                    }
                        break;
                }
            }
            html += '</div>';
            return html;
        },

        /**
         * send Result to screen
         *
         * @param {object} result
         * @param {result} config
         * @param {point} config
         * @param {dataCorrect} config
         *
         * Don't remove class 'answer-correct' & 'answer-incorrect'
         */
        sendResult: function (config, result, point, dataCorrect) {


            var html_dataCorrect = this.showAnswerCorrect(config);

            var result_wrong_header = $([
                '<div><div class="modal-result-wrapper">',
                    '<div class="modal-result">',
                        '<i class="el-icons el-x"></i>',
                        '<div class="modal-result-txt"><p class="bold">残念</p></div>',
                    '</div>',
                    '<img src="/images/icons/answer-incorrect-bunny.png" alt="" class="bunny img-responsive">',
                    '<div class="score">',
                        '<span class="text">獲得スコア</span>',
                        '<span class="number">0</span>',
                    '</div>',
                '</div></div>'
            ].join(''));

            var result_right_header = $([
                '<div><div class="modal-result-wrapper">',
                    '<div class="modal-result">',
                        '<i class="el-icons el-circle-o"></i>',
                        '<div class="modal-result-txt"><p class="bold">正解</p></div>',
                    '</div>',
                        '<img src="/images/icons/answer-correct-bunny.png" alt="" class="bunny img-responsive">',
                    '<div class="score">',
                        '<span class="text">獲得スコア</span>',
                        '<span class="number">' + point + '</span>',
                    '</div>',
                '</div></div>'
            ].join(''));

            var result_footer = $([
                '<div class="media">',
                    '<div class="media-body text-left">',
                        '',
                    '</div>',
                    '<div class="media-right">',
                        '<button id="next-question" type="button" class="x-btn-next btn btn-green btn-block mt0">レッスンの続きを⾒る</button>',
                    '</div>',
                '</div>'
            ].join(''));


            $('.modal-questions.in .modal-header .timer').remove();
            $('.modal-questions.in .modal-footer').html(result_footer.html());


            // hide button prev when wrong_return_second == null
            if(config.drillObject.wrong_return_second == null){
                $('.modal-questions.in .x-btn-prev').remove();
            }

            // show dataCorrect
            $('.modal-questions.in .modal-body').removeClass('timeout').html(html_dataCorrect);



            if (result == true) {

                // play sound
                var sd = $('.x-sound-correct');
                sd[0].currentTime = 0;
                sd[0].play();

                $('.modal-questions.in .modal-content').addClass('answer-correct');
                if($('.modal-questions.in .modal-result-wrapper').length==0){
                    $('.modal-questions.in .modal-header').prepend(result_right_header.html());
                }
            } else {

                // play sound
                var sd = $('.x-sound-wrong');
                sd[0].currentTime = 0;
                sd[0].play();

                $('.modal-questions.in .modal-content').addClass('answer-incorrect');
                if($('.modal-questions.in .modal-result-wrapper').length==0){
                    $('.modal-questions.in .modal-header').prepend(result_wrong_header.html());
                }
            }

            $( document ).ready(function(){
                $('body').on('click', '#next-question', function(){
                    alert('s');
                });
            });

        },

        /**
         * Send Answer to server
         *
         * @param {object} config
         */
        sendAnswer: function (config) {

            // stop sound
            var sd = $('.x-sound-drill');
            sd[0].pause();

            if (config.drillType != undefined) {

                var result = false;
                var point = 0;
                var dataCorrect = null;

                switch (config.drillType) {
                    case 'single':
                    {
                        result = config.drillAnswer;
                    }
                        break;

                    case 'text':
                    {
                        var newallAnswers = _.pluck(config.drillObject.answers, 'correct');
                        result = _.isEqual(config.drillAnswer, newallAnswers[0]);
                    }
                        break;

                    case 'multiple':
                    {
                        var newallAnswers = _.pluck(config.drillObject.answers, 'correct');
                        result = _.isEqual(config.drillAnswer, _.compact(newallAnswers));
                    }
                        break;

                    case 'multi_text':
                    {
                        var newallAnswers = _.pluck(config.drillObject.answers, 'correct');
                        result = _.isEqual(config.drillAnswer, newallAnswers);
                    }
                        break;

                    case 'multi_field':
                    {
                        var newallAnswers = _.pluck(config.drillObject.answers, 'correct');
                        result = _.isEqual(config.drillAnswer, _.compact(newallAnswers));
                    }
                        break;

                    case 'group':
                    {
                        // check empty item answers
                        var newdrillCheck = _.rest(config.drillAnswer);
                        var newallAnswers = _.values(_.groupBy(config.drillObject.answers, 'correct'));

                        _.each(newdrillCheck, function (v, k) {
                            var c1 = _.pluck(newallAnswers[k], 'text');
                            result = (_.difference(v, c1).length == 0 ? true : false);
                        });
                    }
                        break;

                    case 'sort':
                    {
                        var newallAnswers = _.pluck(config.drillObject.answers, 'correct').sort(function (a, b) {
                            return a - b
                        });
                        result = _.isEqual(config.drillAnswer, newallAnswers);
                    }
                        break;

                    default:
                    {
                        //console.log(result,config.drillType);
                    }
                        break;
                }

                if (result == true) {
                    point = 55;
                }

                this.sendResult(config, result, point, dataCorrect);

            }
        }
    });

})();
