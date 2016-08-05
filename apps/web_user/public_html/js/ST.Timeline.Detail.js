var ST = ST || {};

(function(){

    "use strict";

    /**
     * Timeline Detail Class
     *
     * @package app
     * @version
     * @author Duy Ton <duytt@nal.vn>
     * @license
     */
    ST.Timeline = Backbone.View.extend({

        listTimelines: [],


        /**
         * Autoload
         */
        initialize: function (config) {

            config = config || {};

            $(".timeline-detail").each(_.bind(function(index, el) {
                this.listTimelines.push( new ST.TimelineDetail({
                    el: el
                }));
            }, this));

        },


    });

    ST.TimelineDetail = Backbone.View.extend({

        events: {
            'click a.add_good': 'addGood'
        },

        addGoodEl: null,
        goodTotalEl: null,
        commentTotalEl: null,
        timelineId: null,
        timelineUserId: null,
        submitCommentEl: null,
        commentEl: null,
        showMoreEl: null,
        lastTimeRequest: null,
        oldestTimeRequest: null,
        timeout:6000,
        isLoadingNewComments: false,
        fullDetail: false,
        hasOldestComments: false,
        isLoadingOldComments: false,
        countAutoLoadOld: 0,

        /**
         * Autoload
         */
        initialize: function (config) {

            this.timelineId = parseInt(this.$el.data('timeline_id'));
            this.timelineUserId = parseInt(this.$el.data('user_id'));
            this.addGoodBtnEl = this.$el.find('a.add_good');
            this.goodTotalEl = this.$el.find('.good_total');
            this.commentTotalEl = this.$el.find('.current-comment');

            // Config for timeline full detail page
            if (config.full_detail) {
                this.lastTimeRequest = config.last_time;
                this.oldestTimeRequest = config.oldest_time;
                this.fullDetail = true;
                this.submitCommentEl = $('#comment_submit');
                this.commentEl = $('#comment_content');
                this.showMoreEl = $('#show_more');
                this.hasOldestComments = config.has_oldest_comments;

                // Event when keyup
                this.commentEl.keyup(_.bind(function () {
                    this.submitCommentEl.prop('disabled', !this.commentEl.val());
                }, this));

                this.submitCommentEl.click(_.bind(this.submitComment, this));

                this.autoScrollListComments();

                setInterval(this.autoLoadNewComments.bind(this), this.timeout);

                // Event when scroll list comments to top, ajax load old comments
                $(".list-comment").scroll(_.bind(this.loadOldestComments, this));

                // Event when click show more
                this.showMoreEl.click(_.bind(this.ajaxLoadMoreOldComments, this));

                if (!this.hasOldestComments) {
                    this.showMoreEl.hide();
                }
            }
        },

        /**
         * Render
         */
        render: function () {

        },

        /**
         * Auto scroll list comment to bottom
         */
        autoScrollListComments: function () {
            $(".list-comment").animate({
                scrollTop: $(".list-comment").prop('scrollHeight')
            }, 500);
        },

        /**
         * Add good
         * @param e
         */
        addGood: function (e) {
            e.preventDefault();

            // If target has not class disable, so that current user never add good to this timeline
            if (!this.addGoodBtnEl.hasClass('disable')) {
                $.post('/timeline/add_good', {
                    timeline_id: this.timelineId,
                    target_id: this.timelineUserId
                }).done(_.bind(function (res) {

                    if(res) {
                        this.addGoodBtnEl.addClass('disable');
                        this.addGoodBtnEl.removeClass('active');
                        this.goodTotalEl.html(res.total);

                        if (res.trophy.image_key) {
                            $("#get_trophy_image").prop('src', '/image/show/'+res.trophy.image_key);
                            $('#get_trophy_title').html(res.trophy.name);
                            $('#get_trophy_description').html(res.trophy.description);
                        }

                        if (res.point) {
                            $('#point_title').html(res.point.title_modal);
                            $('#point_number').html(res.point.base_point * res.point.campaign);
                        }

                        if (res.trophy.image_key && !res.point) {
                            $('#modalGetTrophy').modal();
                        }

                        if (!res.trophy.image_key && res.point) {
                            $('#modalPoint').modal();
                        }

                        if (res.trophy.image_key && res.point) {
                            $('#modalGetTrophy').modal();
                            $('#modalGetTrophy').on('hidden.bs.modal', function(){
                                $('#modalPoint').modal();
                            });
                        }
                    }

                }, this))
            }
        },

        /**
         * Submit comment
         * @param e
         */
        submitComment: function (e) {
            this.submitCommentEl.prop('disabled', true);
            $('.loading-send').show();

            $.post('/timeline/post_comment', {
                timeline_id: this.timelineId,
                target_id: this.timelineUserId,
                content: this.commentEl.val()
            }).done(_.bind(function (res) {

                this.commentEl.val('');
                $('.loading-send').hide();

                this.commentTotalEl.html(res.total);
                this.autoLoadNewComments();

                this.autoScrollListComments();

            }, this));
        },

        /**
         * Scroll top event
         */
        loadOldestComments: function () {
            var scrollTop = $('.list-comment').scrollTop();
            if ( scrollTop < this.showMoreEl.height() && this.countAutoLoadOld < 4) {
                ++this.countAutoLoadOld;
                this.ajaxLoadMoreOldComments();
            }
        },
        /**
         * Ajax load old comment
         */
        ajaxLoadMoreOldComments: function () {

            if (!this.hasOldestComments) {
                return;
            }

            if (this.isLoadingOldComments) {
                return;
            }

            this.isLoadingOldComments = true;

            $(".loading").show();
            this.showMoreEl.hide();

            var beforeHeight = $(".list-comment").prop('scrollHeight');

            $.post('/timeline/get_list_old_comments', {
                timeline_id: this.timelineId,
                time_request: this.oldestTimeRequest
            }).done(_.bind(function (res) {
                $(".loading").hide();
                this.showMoreEl.show();
                if (res.length > 0) {

                    var html = '';

                    res.forEach(_.bind(function (entry) {

                        html = this.render_comment(entry) + html;
                        this.oldestTimeRequest = entry.created_at;

                    }, this));

                    $('#list_comments').prepend(html);
                }

                var currentHeight = $(".list-comment").prop('scrollHeight');

                $(".list-comment").scrollTop(currentHeight - beforeHeight + this.showMoreEl.height());

                if (res.length < 20) {
                    this.hasOldestComments = false;
                    this.showMoreEl.hide();
                }

                this.isLoadingOldComments = false;

            }, this));
        },
        /**
         * Render comment
         * @param entry
         * @returns {string}
         */
        render_comment: function (entry) {
            var html = '';
            if (!entry.avatar_id) {
                entry.avatar_id = entry.primary_type == 'parent' ? 12 : 2;
            }
            entry.content = entry.content.replace(/[\n\r]/g, "<br />");
            
            var html = '<li class="item">';
            html += '<div class="activity-content">';
            html += '<div class="head clearfix">';
            html += '<a class="thumb" href="/dashboard/' + entry.user_id + '">';
            html += '<img src="/images/avatar/' + entry.avatar_id + '.png" alt="">';
            html += '</a>';
            html += '<div class="center">';
            html += '<div class="status">' + entry.nickname + '</div>';
            html += '<div class="comment">' + entry.content + '</div>';
            html += '</div>';
            html += '<div class="time x-time-format"><span class="date pull-right c-blue-light common-time" data-time="'+entry.created_at+'"></span></div>';
            html += '</div>';
            html += '</div>';
            html += '</li>';

            return html;
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
         * auto load new comments
         */
        autoLoadNewComments: function () {

            if (this.isLoadingNewComments) {
                return;
            }

            this.isLoadingNewComments = true;

            $.post('/timeline/get_list_new_comments', {
                timeline_id: this.timelineId,
                time_request: this.lastTimeRequest
            }).done(_.bind(function (res) {
                this.isLoadingNewComments = false;

                if (res.last_time) {
                    this.lastTimeRequest = res.last_time;
                }

                if (res.items.length > 0) {
                    res.items.forEach(_.bind(function(entry) {
                        $('#list_comments').append(this.render_comment(entry));
                    }, this));
                }

            }, this));
        }
    });
})();
