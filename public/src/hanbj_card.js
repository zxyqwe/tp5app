var card = (function ($, w, undefined) {
    'use strict';
    var init = function () {
        var $table = $('#table');
        $table.bootstrapTable({
            'pageSize': 20,
            formatSearch: function () {
                return '搜索会员编号';
            }
        });
    };
    return {
        init: init
    };
})(jQuery, window);