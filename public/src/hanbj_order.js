var order = (function ($, w, undefined) {
    'use strict';
    var $table;
    var fee_handle = function (n, v) {
        return n + (parseInt(v) + 1) + '年';
    };
    var handle = function (y, v) {
        switch (y) {
            case '1':
                return fee_handle('会费', v);
        }
    };
    var init = function () {
        $table = $('#table');
        $table.bootstrapTable({
            'pageSize': 20,
            responseHandler: function (res) {
                var msg = res.rows;
                for (var i in msg) {
                    var y = msg[i].y;
                    var v = msg[i].v;
                    msg[i].y = handle(y, v);
                }
                return res;
            }
        });
    };
    return {
        init: init
    };
})(jQuery, window);