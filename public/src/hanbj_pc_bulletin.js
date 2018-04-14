var bulletin = (function ($, w, undefined) {
    'use strict';
    var init = function () {
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
                    var tmp = time - parseInt(msg[i].t) - msg[i].b;
                    msg[i].o = w.repeat_icon(alr, msg[i].b) + w.repeat_icon(nye, tmp);
                }
                return res;
            },
            formatSearch: function () {
                return '搜索会员编号';
            }
        });
    };
    return {
        init: init
    };
})(jQuery, window);

var bonus = (function ($, w, undefined) {
    'use strict';
    var init = function () {
        var $table = $('#table');
        $table.bootstrapTable();
    };
    return {
        init: init
    };
})(jQuery, window);

var login = (function ($, Vue, w, undefined) {
    'use strict';
    var $capt_img, iter = 0, vmain, limit = 60;
    var vue_init = function () {
        vmain = new Vue({
            el: '#vmain',
            data: {
                sec: limit
            },
            computed: {
                rto: function () {
                    return this.sec * 100.0 / limit;
                }
            }
        });
    };
    var heartbeat = function () {
        $.ajax({
            type: "POST",
            url: w.u11,
            dataType: "json",
            success: function (msg) {
                location.reload(true);
                location.search += '&_=' + Date.now();
            },
            error: function (jqXHR, msg, ethrow) {
                setTimeout(heartbeat, 1000);
            }
        });
    };
    var loop = function () {
        iter++;
        iter %= limit;
        if (iter === 0) {
            $capt_img.attr('src', w.u11 + '?_=' + new Date());
        }
        vmain.sec = limit - iter;
    };
    var init = function () {
        $capt_img = $('#capt_img');
        vue_init();
        setInterval(loop, 1000);
        heartbeat();
    };
    return {
        init: init
    };
})(jQuery, Vue, window);