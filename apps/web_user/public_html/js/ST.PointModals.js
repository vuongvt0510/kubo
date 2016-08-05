var ST = ST || {};

(function(){

    "use strict";

    ST.PointModals = Backbone.View.extend({

        modals : {},
        modalIndex : 0,
        modalID : null,
        showNickname : false,

        /**
         * Autoload
         */
        initialize: function (config) {

            this.modals = config.modals;
            this.showNickname = config.showNickname
            $("#getPoint_close").click(_.bind(this.closeModal, this));
        },

        showModal : function () {
            var modal = this.modals[this.modalIndex];
            this.modalID = modal.id;
            $('#point_title').html(modal.title_modal);
            $('#point_number').html(modal.base_point * modal.campaign);

            $('#modalPoint').modal({
                backdrop: 'static',
                keyboard: false
            });
        },

        closeModal: function () {

            $('#modalPoint').modal('hide');
            $.ajax({
                method: "POST",
                url: "/top/change_modal_shown",
                data: {
                    id: this.modalID
                },
                dataType: 'json'
            });
            this.modalIndex+=1;
            if (this.modals.length > this.modalIndex) {

                $('#modalPoint').on('hidden.bs.modal', _.bind(function(){
                    this.showModal();
                }, this));
            } else {
                if (this.showNickname == true) {
                    $("#modalUsername").modal({
                        keyboard: false,
                        backdrop: 'static'
                    });
                }
            }
        }
    });
})();
