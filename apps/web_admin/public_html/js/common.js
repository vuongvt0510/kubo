var Helpers = function() {
    /*Toastr options*/
    if(toastr) toastr.options = {
        "closeButton": true,
        "debug": false,
        "positionClass": "toast-top-right",
        "onclick": null,
        "showDuration": "1000",
        "hideDuration": "1000",
        "timeOut": "5000",
        "extendedTimeOut": "1000",
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
    };

    return {
        showNotification: function(message, type)
        {
            if(!toastr) return;
            switch (type)
            {
                case 'warning':
                {
                    toastr.warning(message);
                } break;
                case 'success':
                {
                    toastr.success(message);
                } break;
                case 'error':
                {
                    toastr.error(message);
                } break;
                case 'info':
                {
                    toastr.info(message);
                } break;
            }
        }
    }
}();