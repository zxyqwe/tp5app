var bulletin = (function ($, w, undefined) {
    'use strict';
    var repeat = function (target, n) {
        var s = target, total = "";
        while (n > 0) {
            if (n % 2 == 1) {
                total += s;
            }
            if (n == 1) {
                break;
            }

            s += s;
            n = n >> 1;//相当于将n除以2取其商，或者说是开2次方
        }
        return total;
    };
    var init = function () {
        $('#back').click(function () {
            location.href = '/hanbj';
        });
        var $table = $('#table');
        var alr = $('#ggly').html();
        var nye = $('#rgly').html();
        var time = new Date();
        time = time.getFullYear() + 1;
        $table.bootstrapTable({
            'pageSize': 20,
            responseHandler: function (res) {
                var msg = res.rows;
                for (var i in msg) {
                    if (msg[i].n === null) {
                        msg[i].n = 0;
                    }
                    msg[i].s = parseInt(msg[i].s) - parseInt(msg[i].n) * 2;
                    var tmp = time - parseInt(msg[i].t) - msg[i].s;
                    tmp = tmp > 0 ? tmp : 0;
                    msg[i].o = repeat(alr, msg[i].s) + repeat(nye, tmp);
                }
                return res;
            }
        });
    };
    return {
        init: init
    };
})(jQuery, window);