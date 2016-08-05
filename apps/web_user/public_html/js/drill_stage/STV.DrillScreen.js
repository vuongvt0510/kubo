var STV = STV || {};

/**
 * Drill Base Screen
 *
 * @type {*|void|Object}
 */
(function () {
    "use strict";

    STV.DrillScreen = Backbone.View.extend({
        el: '#drill-screen',
        baseUrl: '/js/drill',

        drillObject: [],
        secondObj: {},

        drillResults: [],

        currentId: 0,
        currentDrill: [],
        currentResults: [],

        modVP: null,

        tweetButtons: true

    });

})();
