var STV = STV || {};

/**
 * Drill Single Answer
 *
 * @type {*|void|Object}
 */
(function () {
    "use strict";

    STV.DrillScreen.Text = STV.DrillScreen.Base.extend({
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

            _.each(config, function (vid) {
                $('body').on('click', '.modal-questions.in .x-submit-' + vid.id, _.bind(function () {
                    $('.modal-questions.in .modal-body').addClass('disable');
                    config.drillType = 'text';
                    config.drillAnswer = $('.modal-questions.in input[type="text"]').val();
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
                        urlImage += '<div class="col-sm-' + Math.ceil(12 / config.question_images.length) + '">' +
                            '<figure><img src="/drill_images/' + v.url + '" alt="' + v.caption + '"></figure>' +
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
                var quesField = "";

                if (config.answers) {
                    quesField += '<div class="row modal-questions-que">';
                    _.each(config.answers, function (v) {
                        quesField += '<div class="col-xs-' + Math.ceil(12 / config.answers.length) + '">';

                        // escape {input}
                        v.text = v.text.replace('｛input｝', '{input}');
                        v.text = v.text.replace('｛Input｝', '{input}');

                        var reString = '<div class="form-group"><label>' + v.text.replace('{input}', ' <input class="form-control" type="text" name="text-answers" value=""> ') + '</label></div>'
                        // If type answers is image
                        if (v.image_url !== null) {
                            quesField += reString;
                        } else {
                            quesField += reString;
                        }
                        quesField += '</div>';

                    });
                    quesField += '</div>';
                }
                return quesField;
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
                                            '   <div class="dots-active"></div>' +
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
                                '<form action="" class="form-inline text-center">' +
                                    dataQuestionImage(config) +
                                    dataQuestionField(config) +
                                '</form>' +
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
