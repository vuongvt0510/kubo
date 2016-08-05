var STV = STV || {};

/**
 * Drill Single Answer
 *
 * @type {*|void|Object}
 */
(function () {
    "use strict";

    STV.DrillScreen.Base = Backbone.View.extend({

        currentUser: null,

        /**
         * Constructor
         *
         * @param config {Object} Config
         */
        initialize: function (config) {
            config = config || {};

            this.currentUser = config.currentUser;

        },

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

            //correct answers → mustn't be random. just follow the order in spreadsheet.
            config.drillObject.answers = _.sortBy(config.drillObject.answers, 'order');

            if (config.drillType != undefined) {
                switch (config.drillType) {
                    case 'single':
                    {
                        if (config.drillObject.answers[0].image_url == null) {
                            dataCorrect = _.pluck(_.where(config.drillObject.answers, {correct: true}), 'text');
                            _.each(dataCorrect, function (v) {
                                html += v;
                            });
                        } else {
                            dataCorrect = _.pluck(_.where(config.drillObject.answers, {correct: true}), 'image_url');
                            _.each(dataCorrect, function (v) {
                                html += '<img width="150" src="/drill_images/' + v + '">';
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
                        if (config.drillObject.answers[0].image_url == null) {
                            dataCorrect = _.pluck(_.where(config.drillObject.answers, {correct: true}), 'text');
                            _.each(dataCorrect, function (v, k) {
                                html += v;
                                if (k + 1 < dataCorrect.length) {
                                    html += '/';
                                }
                            });
                        } else {
                            dataCorrect = _.pluck(_.where(config.drillObject.answers, {correct: true}), 'image_url');
                            _.each(dataCorrect, function (v, k) {
                                html += '<img width="150" src="/drill_images/' + v + '">';
                                if (k + 1 < dataCorrect.length) {
                                    html += '/';
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
                            if (k + 1 < dataCorrect.length) {
                                html += '/';
                            }
                        });
                    }
                        break;

                    case 'multi_field':
                    {
                        if (config.drillObject.answers[0].image_url == null) {
                            dataCorrect = _.pluck(_.where(config.drillObject.answers, {correct: true}), 'text');
                            _.each(dataCorrect, function (v, k) {
                                html += v;
                                if (k + 1 < dataCorrect.length) {
                                    html += '/';
                                }
                            });
                        } else {
                            dataCorrect = _.pluck(_.where(config.drillObject.answers, {correct: true}), 'image_url');
                            _.each(dataCorrect, function (v, k) {
                                html += '<img width="150" src="/drill_images/' + v + '">';
                                if (k + 1 < dataCorrect.length) {
                                    html += '/';
                                }
                            });
                        }
                    }
                        break;

                    case 'group':
                    {
                        var dataCorrect_temp = _.values(_.groupBy(config.drillObject.answers, 'correct'));
                        html += '<div class="row mt20 drill-groupshow">';

                        if (config.drillObject.answers[0].image_url == null) {
                            _.each(dataCorrect_temp, function (v, k) {
                                html += '<div class="col-sm-6"><div class="drill-groupshow-in">'
                                _.each(v, function (vl, ke) {
                                    html += vl.text;
                                    if (ke + 1 < v.length) {
                                        html += '/';
                                    }
                                });
                                html += '</div></div>'
                            });
                        } else {
                            _.each(dataCorrect_temp, function (v, k) {
                                html += '<div class="col-sm-6"><div class="drill-groupshow-in">'
                                _.each(v, function (vl, ke) {
                                    html += '<img width="50" src="/drill_images/' + vl.image_url + '">';
                                    if (ke + 1 < v.length) {
                                        html += '/';
                                    }
                                });
                                html += '</div></div>'
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
                            if (k + 1 < dataCorrect.length) {
                                html += ' ';
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
        sendResult: function (config, result) {

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
                        '<span class="number">0</span>',
                    '</div>',
                '</div></div>'
            ].join(''));

            var result_footer = $([
                '<div class="media">',
                    '<div class="media-body text-left">',
                        '',
                    '</div>',
                    '<div class="media-right">',
                        '<button type="button" class="x-btn-next btn btn-green btn-block mt0">レッスンの続きを見る</button>',
                        '<button type="button" class="x-btn-prev btn btn-green btn-block">',
                        '<i class="el-icons el-history"></i> もう一度レッスンを見る',
                        '</button>',
                    '</div>',
                '</div>'
            ].join(''));


            $('.modal-questions.in .modal-header .timer').remove();
            $('.modal-questions.in .modal-footer').html(result_footer.html());


            // hide button prev when wrong_return_second == null
            if (config.drillObject.wrong_return_second == null) {
                $('.modal-questions.in .x-btn-prev').remove();
            }

            // show dataCorrect
            $('.modal-questions.in .modal-body').removeClass('timeout').html(html_dataCorrect);


            if (result && config.timeAnswer > 0) {

                $.ajax({
                    type: "POST",
                    url: '/question/get_score',
                    data: {
                        'second': config.drillObject.time_limit - config.timeAnswer,
                        'limit': config.drillObject.time_limit,
                    },
                    dataType: 'json',
                    success: function(res) {
                        if (res.submit) {
                            $('.modal-questions.in .modal-result-wrapper .number').text(res.result.total);
                        }
                    }
                });

                $('.modal-questions.in .modal-content').addClass('answer-correct');
                if ($('.modal-questions.in .modal-result-wrapper').length == 0) {
                    $('.modal-questions.in .modal-header').prepend(result_right_header.html());
                }

                // play sound
                var sd = $('.x-sound-correct');
                sd[0].currentTime = 0;
                sd[0].play();
            } else {

                $('.modal-questions.in .modal-content').addClass('answer-incorrect');
                if ($('.modal-questions.in .modal-result-wrapper').length == 0) {
                    $('.modal-questions.in .modal-header').prepend(result_wrong_header.html());
                }

                // play sound
                var sd = $('.x-sound-wrong');
                sd[0].currentTime = 0;
                sd[0].play();
            }

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

            //EL_STAGE1-1564 [Drill] 連続質問で、「間違った回答」を除いて回答すれば、順序関係なくすべて正解になってしまう。
            //Order of user answer need to be checked in 'Multi_field' Questions
            config.drillObject.answers = _.sortBy(config.drillObject.answers, 'order');

            if (config.drillType != undefined) {

                var result = false;

                switch (config.drillType) {
                    case 'single':
                    {
                        result = config.drillAnswer;
                    }
                        break;

                    case 'text':
                    {
                        var newallAnswers = _.pluck(config.drillObject.answers, 'correct');
                        result = _.isEqual(this._strReplaceJP(config.drillAnswer), newallAnswers[0]);
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
                        var newdrillCheck = _.map(config.drillAnswer, function(num){ return this._strReplaceJP(num); }, this);

                        result = _.isEqual(newdrillCheck, newallAnswers);
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
                        if (config.drillAnswer[0].length == 0){
                            var result_tt = [];
                            var newdrillCheck = _.rest(config.drillAnswer);

                            _.each(newdrillCheck, function (v) {
                                _.each(v, function (vl,ke) {
                                    // convert v.text to v.correct
                                    if(config.drillObject.answers[0].image_url != null){
                                        v[ke] = _.values(_.pick(_.findWhere(config.drillObject.answers, {image_url: vl}), 'correct')).toString();
                                    } else {
                                        v[ke] = _.values(_.pick(_.findWhere(config.drillObject.answers, {text: vl}), 'correct')).toString();
                                    }
                                });
                                //Produces a duplicate v.correct of the array
                                result_tt.push(_.uniq(v).length == 1 ? true : false); // [false, true];
                            });
                            result = _.every(result_tt, _.identity); //[false, true] --> false
                        }
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


                this.sendResult(config, result);

            }
        },

        _strReplaceJP: function (str) {
            var hankaku = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-+ ';
            var zenkaku = '１２３４５６７８９０ａｂｃｄｅｆｇｈｉｊｋｌｍｎｏｐｑｒｓｔｕｖｗｘｙｚＡＢＣＤＥＦＧＨＩＪＫＬＭＮＯＰＱＲＳＴＵＶＷＸＹＺ－＋　';

            for (var i = 0, n = zenkaku.length; i < n; i++) {
                str = str.replace(new RegExp(zenkaku[i], 'gm'), hankaku[i]);
            }
            return str.replace(/^\s+|\s+$/g, ''); // trim head and tail white space
        }

    });

})();
