var bulletin = (function ($, w, undefined) {
    'use strict';
    var $table;
    var init = function () {
        $table = $('#table');
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
        init: init,
        get_table: function () {
            return $table;
        }
    };
})(jQuery, window);

var bonus = (function ($, w, undefined) {
    'use strict';
    var $table;
    var init = function () {
        $table = $('#table');
        $table.bootstrapTable();
    };
    return {
        init: init,
        get_table: function () {
            return $table;
        }
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
            data: {
                _ajax: 1,
            },
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

var vote = (function ($, Vue, w, undefined) {
    'use strict';
    var vmain;
    var refresh = function (target_year) {
        w.waitloading();
        $.ajax({
            type: "GET",
            url: w.u24 + '/year/' + target_year,
            data: {
                _ajax: 1,
            },
            dataType: "json",
            success: function (msg) {
                var zg = [], tmp, rto, i;
                for (i in msg.zg.detail) {
                    tmp = msg.zg.detail[i];
                    rto = 100 * tmp / msg.zg.tot;
                    zg.push({
                        n: i, v: tmp, rto: rto.toFixed(2)
                    })
                    ;
                }
                var pw = [];
                for (i in msg.pw.detail) {
                    tmp = msg.pw.detail[i];
                    rto = 100 * tmp / msg.pw.tot;
                    pw.push({n: i, v: tmp, rto: rto.toFixed(2)});
                }
                vmain.ans = {zg: zg, pw: pw, zg_tot: msg.zg.tot, pw_tot: msg.pw.tot};
                vmain.refresh = msg.ref;
                vmain.last = msg.last;
                vmain.year = msg.year;
            },
            error: function (jqXHR, msg, ethrow) {
                w.msgto(jqXHR, msg, ethrow);
            },
            complete: function () {
                w.cancelloading();
            }
        });
        setTimeout(refresh, 600000);
    };
    var init = function (target_year) {
        vmain = new Vue({
            el: '#body',
            data: {
                ans: {
                    zg: [],
                    pw: []
                },
                refresh: '',
                last: '',
                year: 0
            },
            methods: {},
            ready: function () {
                refresh(target_year);
            }
        });
    };
    return {
        init: init
    };
})(jQuery, Vue, window);