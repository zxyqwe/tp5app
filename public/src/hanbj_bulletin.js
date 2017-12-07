var bulletin = (function ($, w, undefined) {
    'use strict';
    var repeat = function (target, n) {
        var s = target, total = "";
        while (n > 0) {
            if (n % 2 === 1) {
                total += s;
            }
            if (n === 1) {
                break;
            }

            s += s;
            n = n >> 1;//相当于将n除以2取其商，或者说是开2次方
        }
        return total;
    };
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
                    tmp = tmp > 0 ? tmp : 0;
                    msg[i].o = repeat(alr, msg[i].b) + repeat(nye, tmp);
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
        $table.bootstrapTable({
            responseHandler: function (res) {
                var base = 0;
                var tmpb = 0;
                var msg = res;
                var data = [];
                for (var i in msg) {
                    var bonus = parseInt(msg[i].o);
                    if (bonus !== tmpb) {
                        base = parseInt(i) + 1;
                        if (base > 50) {
                            break;
                        }
                        tmpb = bonus;
                    }
                    msg[i].i = base;
                    data.push(msg[i]);
                }
                return data;
            }
        });
    };
    return {
        init: init
    };
})(jQuery, window);

var login = (function ($, Vue, w, undefined) {
    'use strict';
    var $capt_img, iter = 0, vmain;
    var vue_init = function () {
        vmain = new Vue({
            el: '#vmain',
            data: {
                sec: 30
            },
            computed: {
                rto: function () {
                    return this.sec * 100.0 / 30;
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
            }
        });
    };
    var loop = function () {
        iter++;
        iter %= 30;
        if (iter === 0) {
            $capt_img.attr('src', w.u11 + '?_=' + new Date());
        }
        vmain.sec = 30 - iter;
        heartbeat();
    };
    var init = function () {
        $capt_img = $('#capt_img');
        vue_init();
        setInterval(loop, 1000);
    };
    return {
        init: init
    };
})(jQuery, Vue, window);