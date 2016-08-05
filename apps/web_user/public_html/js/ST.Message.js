var ST = ST || {};

(function(){

    "use strict";

    /**
     * Message Timeline Class
     */

    ST.Message = Backbone.View.extend({

        avatar: '',
        nickname: '',
        current_user_id: '',
        room_id: '',
        timeout: 10000,
        last_time_request: '',
        oldest_created_at: '',
        count_scroll: 0,
        max_scroll_load: 4,
        has_oldest_message: true,
        auto_load_oldest: false,

        submit_el: null,
        content_el: null,

        message_tpl: '\
            <li class="item clearfix">\
                <div class="row">\
                    <div class="pull-left avatar-col">\
                        <img src="/images/avatar/<%= avatar %>.png" alt="avatar" class="avatar img-responsive" width="50px" height="50px">\
                    </div>\
                    <div class="message">\
                        <p class="user-name"><%= nickname %></p>\
                        <% if (has_content) { %>\
                        <p class="message-bubble">\
                        <% } %>\
                        <%= message %>\
                        <% if (has_deck) { %>\
                        <ul class="deck-list-line list-unstyled"> \
                            <li class="item"> \
                                <a class="item-inner" href="/deck/<%= deck.id %>">\
                                    <div class="left"> \
                                        <div class="thumb x-img-liquid">\
                                            <img src="/image/show/<%= deck.image_key %>" class="img-responsive" alt="">\
                                        </div>\
                                    </div>\
                                    <div class="right text-left">\
                                        <p style="color: black;" title="<% deck.name %>"><%= deck.name %></p>\
                                        <div class="subject bg-<% deck.subject.color %>"><%= deck.subject.short_name %></div>\
                                    </div>\
                                </a>\
                            </li>\
                        </ul>\
                        <% } %>\
                        <span class="date c-blue-light common-time" data-time="<%= created_at %>" ></span>\
                        <% if (has_content) { %>\
                        </p>\
                        <% } %>\
                    </div>\
                </div>\
            </li>',

        /**
         * Autoload
         */
        initialize: function (config) {
            config = config || {};

            // Initialize variables
            this.avatar = config.avatar;
            this.nickname = config.nickname || '';
            this.current_user_id = config.current_user_id || '';
            this.last_time_request = config.last_time_request || '';
            this.oldest_created_at = config.oldest_created_at || '';
            this.has_oldest_message = config.has_oldest_message || true;
            this.timeout = config.timeout || this.timeout;
            this.room_id = config.room_id;

            this.submit_el = $('#comment_submit');
            this.content_el = $('#comment_content');

            // Event when paste
            this.submit_el.bind("input paste", _.bind(function () {
                this.submit_el.prop('disabled', !this.content_el.val());
            }, this));

            // Event when keyup
            this.content_el.keyup(_.bind(function () {
                this.submit_el.prop('disabled', !this.content_el.val());
            }, this));

            // Bind form event
            $('form').on('submit', _.bind(this.onFormSubmit ,this));

            // Set scroll handler
            $(window).scroll(_.bind(this.loadMessageWhenScroll, this));

            $('#show_more').click(_.bind(this.ajaxLoadMoreOldMessage, this));

            this.render();
        },

        /**
         * Render
         */
        render: function () {
            $('html, body').animate({
                scrollTop: $('#comment_content').offset().top - $( window ).height() + 2 * $('#comment_content').height()
            }, 500, _.bind(function () {
                this.auto_load_oldest = true;
            }, this));

            this.submit_el.prop('disabled', !this.content_el.val());

            // Set interval for auto load new message ajax
            setInterval(this.autoLoadNewMessages.bind(this), this.timeout);
        },

        /**
         * Ajax load old messages
         */
        ajaxLoadMoreOldMessage: function () {

            if (!this.has_oldest_message) {
                return;
            }

            $.ajax({
                type: 'POST',
                url: '/message/list_message_old',
                data: {
                    room_id: this.room_id,
                    last_time: this.oldest_created_at
                },
                dataType: 'json'

            }).done(_.bind(function (res) {

                var result = res.result;

                if (result.length < 20) {
                    this.has_oldest_message = false;
                }

                if(result.length) {
                    // Get last time of message
                    this.oldest_created_at = _.last(result).created_at;

                    _.forEach(result, _.bind(function(entry) {
                        this.appendMessage(entry, 'prepend');
                    }, this));

                    // Check count scroll
                    if (++this.count_scroll >= this.max_scroll_load && this.has_oldest_message ) {
                        // Show button show_more
                        $('#show_more').show();
                    } else {
                        $('#show_more').hide();
                    }
                }

            }, this));

        },
        /**
         * load messages when scroll
         */
        loadMessageWhenScroll: function () {

            if (!this.auto_load_oldest) {
                return ;
            }

            var scrollTop = $(window).scrollTop();

            if (scrollTop == 0) {

                // If count scroll is less than 5 times, auto load ajax to get old messages
                if (this.count_scroll < this.max_scroll_load && this.has_oldest_message) {
                    this.ajaxLoadMoreOldMessage();
                }
            }
        },

        /**
         * Submit message event
         * @param e
         */
        onFormSubmit: function (e) {

            e.preventDefault();

            this.submit_el.prop('disabled', true);

            $('.form-message').removeClass('form-share');
            $('#share_deck_message').hide();

            // call ajax create message
            $.ajax({
                type: 'post',
                url: '/message/create',
                data: {
                    content: this.content_el.val(),
                    room_id: this.room_id,
                    last_time: this.last_time_request
                },

                success: _.bind( function (res) {

                    if (res.result) {

                        var result = res.result;

                        if (result.trophy.image_key) {
                            $("#get_trophy_image").prop('src', '/image/show/'+result.trophy.image_key);
                            $('#get_trophy_title').html(result.trophy.name);
                            $('#get_trophy_description').html(result.trophy.description);
                        }

                        if (result.point) {
                            $('#point_title').html(result.point.title_modal);
                            $('#point_number').html(result.point.base_point * result.point.campaign);
                        }

                        if (result.trophy.image_key && !result.point) {
                            $('#modalGetTrophy').modal();
                        }

                        if (!result.trophy.image_key && result.point) {
                            $('#modalPoint').modal();
                        }

                        if (result.trophy.image_key && result.point) {
                            $('#modalGetTrophy').modal();
                            $('#modalGetTrophy').on('hidden.bs.modal', function(){
                                $('#modalPoint').modal();
                            });
                        }
                    }

                    this.autoLoadNewMessages();

                    this.content_el.val('');
                    this.submit_el.prop('disabled', true);

                }, this)
            });
        },

        /**
         * Append message html to list
         * @param res
         * @param appendType
         */
        appendMessage: function (res, appendType) {
            if (res.avatar_id == 0) {
                if (res.primary_type == 'parent') {
                    res.avatar_id = 12;
                } else {
                    res.avatar_id =2;
                }
            }
            appendType = appendType || 'append';

            var message_template = _.template(this.message_tpl);

            var message_html = message_template({
                has_content: res.message ? 1 : 0,
                message: res.message.replace(/[\n\r]/g, "<br />"),
                avatar: res.avatar_id,
                nickname: res.nickname,
                has_deck: !$.isEmptyObject(res.deck),
                deck: res.deck,
                created_at: res.created_at
            });

            if (appendType == 'append') {
                $('.messages').append(message_html);
            } else {
                $('.messages').prepend(message_html);
            }
        },
        /**
         * auto load new message
         */
        autoLoadNewMessages: function () {
            $.ajax({
                type: 'POST',
                url: '/message/get_list_new_message',
                data: {
                    'room_id' : this.room_id,
                    'last_time': this.last_time_request
                },
                success: _.bind(function(res) {

                    var result = res.result;

                    this.last_time_request = result.last_time;

                    _.forEach(result.items, _.bind(function(entry) {
                        this.appendMessage(entry);
                    }, this));
                }, this)
            });
        }
    });
})();
