var STV = STV || {};

/**
 * Drill Single Answer
 *
 * @type {*|void|Object}
 */
(function () {
    "use strict";

    STV.DrillScreen.Sort = STV.DrillScreen.Base.extend({

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

            var onDragIndex;

            /**
             * plugin dragndrop
             */
            $.getScript('/third_party/dragula.js/dist/dragula.js', function () {
                _.each(config, function (vid) {

                    var containerDragula = [document.querySelector("#dragndrop-" + vid.id + " [data-dropitem='0'")];
                    _.each(config.drillObject.answers, function (v, k) {
                        containerDragula.push(document.querySelector("#dragndrop-" + vid.id + " [data-dropitem='" + (k + 1) + "']"));
                    });

                    dragula(containerDragula)
                        .on('drag', function (el, container) {
                            onDragIndex = parseInt($(container).attr('data-dropitem'));
                        })
                        .on('drop', function (el, container) {
                            var onDropHtml = $(container).children('.jqselection-selectable:not([data-text="' + $(el).attr('data-text') + '"])');

                            // check if item has already data
                            if ($(container).children('.jqselection-selectable').length > 1) {
                                if (parseInt($(container).attr('data-dropItem')) > 0) {
                                    onDropHtml.remove();
                                    $('.modal-questions.in [data-dropitem="' + onDragIndex + '"]').append(onDropHtml.clone());
                                }
                            }
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
                // user can auto answers by clicking
                $('body').on('click', '#modalDrill-' + vid.id + '.modal-questions.in div[data-dropitem="0"] .jqselection-selectable', function () {
                    $(".modal-questions.in .modal-questions-ans .jqdragndrop-drop:empty").first().html(this);
                });

                // revert answers by clicking
                $('body').on('click', '#modalDrill-' + vid.id + '.modal-questions.in .modal-questions-ans .jqselection-selectable', function () {
                    $('.modal-questions.in div[data-dropitem="0"]').append(this);
                });

                $('body').on('click', '.modal-questions.in .x-submit-' + vid.id, _.bind(function () {
                    $('.modal-questions.in .modal-body').addClass('disable');
                    config.drillType = 'sort';

                    var drillAnswerData = [];
                    $('.modal-questions.in .modal-questions-ans').find('.jqselection-selectable').each(function () {
                        drillAnswerData.push(parseInt($(this).attr('data-text')));
                    });

                    config.drillAnswer = drillAnswerData;
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
             * Show questionImage
             */
            function dataQuestionImage(config) {
                var urlImage = "";
                if (config.question_images) {
                    urlImage += '<div class="row modal-questions-ans">';
                    _.each(config.question_images, function (v) {
                        urlImage += '<div class="col-xs-12 col-sm-' + Math.max(3, Math.ceil(12 / config.question_images.length)) + '">' +
                            '<figure><img src="/image/show/' + v.url + '" alt="' + v.caption + '"></figure>' +
                            '</div>';
                    });
                    urlImage += '</div>';
                }
                return urlImage;
            };

            /**
             * Show questionField
             */
            function dataQuestionField(config) {
                var quesField = '';
                if (config.answers) {
                    quesField += '<div class="row modal-questions-ans">';
                    var count = 0;
                    _.each(config.answers, function (v, k) {
                        if (!v.correct) return;
                        quesField += '<div class="col-xs-6 col-sm-3">' +
                            '<div class="jqdragndrop-sort">' +
                            '<span>' + (count + 1) + '</span><div data-dropItem="' + (count + 1) + '" class="jqdragndrop-drop"></div>' +
                            '</div></div>';
                        ++count;
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
                            ansField += '<div class="jqselection-selectable dr-box-img" data-text="' + v.order + '"><img src="/image/show/' + v.image_url + '" alt=""></div>';
                        } else {
                            ansField += '<div class="jqselection-selectable" data-text="' + v.order + '"><span class="dr-box">' + v.text + '</span></div>';
                        }
                    });
                    ansField += '</div></div>';
                }

                return ansField;
            };

            this.html = $(
                '<div class="modal fade modal-questions ' + config.type + '" id="modalDrill-' + config.id + '" tabindex="-1" role="dialog">' +
                    '<div class="modal-dialog" role="document">' +
                        '<div class="modal-content">' +
                            '<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                            '<div class="modal-header">' +
                                '<div class="timer">' +
                                    '<div class="timer-content clearfix">' +
                                        '<div class="text start-text active pull-left">Start</div>' +
                                            '<div class="airplane-wrapper pull-left">' +
                                            '<div class="dots">' +
                                            '   <div class="dots-active"></div>' +
                                            '</div>' +
                                            '<div class="airplane"></div>' +
                                        '</div>' +
                                        '<div class="text time-out-text pull-left">Time out</div>' +
                                    '</div>' +
                                    '<div class="countdown"></div>' +
                                '</div>' +
                                '<div class="modal-basic-title">' + config.question + '</div>' +
                            '</div>' +
                            '<div class="modal-body">' +
                                dataQuestionImage(config) +
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
