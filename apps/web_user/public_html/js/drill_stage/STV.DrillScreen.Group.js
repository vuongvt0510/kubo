var STV = STV || {};

/**
 * Drill Single Answer
 *
 * @type {*|void|Object}
 */
(function () {
    "use strict";

    STV.DrillScreen.Group = STV.DrillScreen.Base.extend({

        /**
         * @var jQueryObject HTML Object to show on page
         */
        html: null,

        /**
         * Constructor
         *
         * @param config {Object} Config
         */
        initialize: function (config) {
            config = config || {};

            this.render(config.drillObject, {});

            var allAnswers = [];
            _.each(config.drillObject.answers, function (v) {
                if (v.image_url != null) {
                    allAnswers.push(v.image_url); // ["ぞう", "ねこ", "いぬ", ...]
                } else {
                    allAnswers.push(v.text); // ["ぞう", "ねこ", "いぬ", ...]
                }
            });

            var answerSort = [];
            answerSort[0] = _.union(allAnswers); // first item is allAnswers

            _.each(config.drillObject.question_groups, function (v, k) {
                answerSort[k + 1] = []; // [allAnswers,[],[]]
            });

            /**
             * plugin dragndrop
             */
            $.getScript('/third_party/dragula.js/dist/dragula.js', function () {
                _.each(config, function (vid) {

                    var containerDragula = [];
                    _.each(answerSort, function (v, k) {
                        containerDragula.push(document.querySelector("#dragndrop-" + vid.id + " [data-dropitem='" + k + "']"));
                    });

                    dragula(containerDragula)
                        .on('drag', function (el, container) {
                            el.className = el.className.replace('ex-moved', '');

                            var index = parseInt($(container).attr('data-dropitem'));
                            var index_remove = answerSort[index].indexOf($(el).attr('data-text'));
                            if (index_remove >= 0) {
                                answerSort[index].splice(index_remove, 1);
                            }

                        })
                        .on('drop', function (el, container) {
                            el.className += ' ex-moved';

                            var index = parseInt($(container).attr('data-dropitem'));
                            answerSort[index].push($(el).attr('data-text'));

                        })
                        .on('over', function (el, container) {
                            container.className += ' ex-over';
                        })
                        .on('out', function (el, container) {
                            container.className = container.className.replace('ex-over', '');
                        });
                });

            });

            _.each(config, function (vid) {
                $('body').on('click', '.modal-questions.in .x-submit-' + vid.id, _.bind(function () {
                    $('.modal-questions.in .modal-body').addClass('disable');
                    config.drillType = 'group';
                    config.drillAnswer = answerSort;
                    config.timeAnswer = parseInt($('.modal-questions.in .countdown').text());
                    this.sendAnswer(config);
                }, this));
            }, this);
        },

        /**
         * Render html of Drill modal
         */
        render: function (config) {
            /**
             * Show questionField
             */
            function dataQuestionField(config) {
                var quesField = '';
                if (config.question_groups) {
                    quesField += '<div class="row modal-questions-ans">';
                    _.each(config.question_groups, function (v, k) {
                        quesField += '<div class="col-xs-6 col-sm-' + Math.ceil(12 / config.question_groups.length) + '">' +
                            '<div class="jqdragndrop-group">' +
                            '<div data-dropItem="' + (k + 1) + '" class="jqdragndrop-drop"></div>' +
                            '</div>' +
                            '</div>';
                    });
                    quesField += '</div>';
                }
                return quesField;
            };

            /**
             * Show answerField
             */
            function dataAnswerField(config) {
                var ansField = '';
                if (config.answers) {
                    ansField += '<div class="modal-questions-que">' +
                        '<div data-dropItem="0" class="jqdragndrop-drop">';
                    _.each(config.answers, function (v) {
                        if (v.image_url != null) {
                            ansField += '<div class="jqselection-selectable dr-box-img" data-text="' + v.text + '"><img src="/drill_images/' + v.image_url + '" alt=""></div>';
                        } else {
                            ansField += '<div class="jqselection-selectable" data-text="' + v.text + '"><span class="dr-box">' + v.text + '</span></div>';
                        }
                    });
                    ansField += '</div></div>';
                }
                return ansField;
            };

            this.html = $(
                '<div class="modal fade modal-questions" id="modalDrill-' + config.id + '" tabindex="-1" role="dialog">' +
                    '<div class="modal-dialog" role="document">' +
                        '<div class="modal-content">' +
                            '<button type="button" class="close" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                            '<div class="modal-header">' +
                                '<div class="timer">' +
                                    '<div class="timer-content clearfix">' +
                                        '<div class="text start-text active pull-left">Start</div>' +
                                        '<div class="airplane-wrapper pull-left">' +
                                            '<div class="dots">' +
                                                '<div class="dots-active"></div>' +
                                            '</div>' +
                                            '<div class="airplane"></div>' +
                                        '</div>' +
                                        '<div class="text time-out-text pull-left">Time out</div>' +
                                    '</div>' +
                                    '<div class="countdown"></div>' +
                                '</div>' +
                                '<div class="modal-question-total"></div>' +
                                '<div class="modal-basic-title">' + config.question + '</div>' +
                            '</div>' +
                            '<div class="modal-body">' +
                                '<div id="dragndrop-' + config.id + '">' +
                                    dataQuestionField(config) +
                                    dataAnswerField(config) +
                                '</div>' +
                            '</div>' +
                            '<div class="modal-footer">' +
                                '<div class="text-right"><button type="button" class="btn btn-green btn-right-arrow x-submit-' + config.id + '">答え合わせ</button></div>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                '</div>');

            this.html.on('click', _.bind(function () {
                /*this.sendAnswer({
                 question_id: 1,
                 answer_id: 2
                 });*/
            }, this));
        },

        /**
         * Open Window on page as modal
         */
        open: function () {
            $('#drill-screen').append(this.html);
        }

    });

})();
