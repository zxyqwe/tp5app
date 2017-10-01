var order = (function ($, w, undefined) {
    'use strict';
    var $table;
    var init = function () {
        $table = $('#table');
        $table.bootstrapTable({
            'pageSize': 20
        });
    };
    return {
        init: init
    };
})(jQuery, window);