var card = (function ($, w, undefined) {
    'use strict';
    var init = function () {
        var $table = $('#table');
        $table.bootstrapTable({
            'pageSize': 20,
            formatSearch: function () {
                return '搜索昵称或会员编号';
            }
        });
        w.codeFormatter = function (value, row) {
            return home.mem_code(value);
        };
        w.cardFormatter = function (value, row) {
            return value === '0' ? '' : '激活';
        };
    };
    return {
        init: init
    };
})(jQuery, window);