/**
 * Created by DuyPhan on 11/30/2015.
 */
var TableDatatablesAjax = function () {
    var handleRecords = function (options) {

        var grid = new Datatable();

        grid.init({
            src: options.target || $("#datatable_ajax"),
            onSuccess: options.onSuccess || function (grid, response) {
                // grid:        grid object
                // response:    json object of server side ajax response
                // execute some code after table records loaded
            },
            onError: options.onError || function (grid) {
                // execute some code on network or other general error
            },
            onDataLoad: options.onDataLoad || function(grid) {
                // execute some code on ajax data load
            },
            loadingMessage: options.loadingMessage || 'Loading...',
            dataTable: { // here you can define a typical datatable settings from http://datatables.net/usage/options

                // Uncomment below line("dom" parameter) to fix the dropdown overflow issue in the datatable cells. The default datatable layout
                // setup uses scrollable div(table-scrollable) with overflow:auto to enable vertical scroll(see: assets/global/scripts/datatable.js).
                // So when dropdowns used the scrollable div should be removed.
                //"dom": "<'row'<'col-md-8 col-sm-12'pli><'col-md-4 col-sm-12'<'table-group-actions pull-right'>>r>t<'row'<'col-md-8 col-sm-12'pli><'col-md-4 col-sm-12'>>",

                "bStateSave": options.saveStateOnCookie || false, // save datatable state(pagination, sort, etc) in cookie.

                "lengthMenu": [
                    [20, 50, 100, -1],
                    [20, 50, 100, "All"] // change per page values here
                ],
                "pageLength": 20, // default record count per page
                "ajax": {
                    "url": options.ajaxUrl || null, // ajax source
                },
                "order": [
                    [1, "asc"]
                ]// set first column as a default sort by asc
            }
        });

        // handle group actionsubmit button click
        grid.getTableWrapper().on('click', '.table-group-action-submit', function (e) {
            e.preventDefault();
            if (grid.getSelectedRowsCount() === 0) {
                var settings = {
                    theme: 'ruby',
                    sticky: false,
                    horizontalEdge: 'top',
                    verticalEdge: 'right'
                };
                $.notific8('zindex', 11500);
                $.notific8($.trim('選択されていません記録'), settings);
            }
            else
            {
                bootbox.dialog({
                    title: options.confirmText.title || "Confirm",
                    message: options.confirmText.message || "Are you sure?",
                    buttons: {
                        cancel: {
                            label: options.confirmText.cancel.label || 'Cancel',
                            className: options.confirmText.cancel.className || 'btn-default',
                            callback: function() {

                            }
                        },
                        danger: {
                            label: options.confirmText.danger.label || 'OK',
                            className: options.confirmText.danger.className || 'btn-danger',
                            callback: function() {
                                var actionType = 'delete';
                                
                                grid.setAjaxParam("customActionType", "group_action");
                                grid.setAjaxParam("customActionName", actionType);
                                grid.setAjaxParam("id", grid.getSelectedRows());
                                grid.getDataTable().ajax.reload();
                                grid.clearAjaxParams();
                            }
                        }
                    }
                });
            }
        });

        grid.clearAjaxParams();
    }

    return {

        //main function to initiate the module
        init: function (options) {
            handleRecords(options);
        }

    };

}();