var fee = (function ($, Vue, w, undefined) {
    'use strict';
    var vmain;
    var init = function () {
        vmain = new Vue({
            el: '#body',
            data: {
                uname: '',
                candy: [],
                res: []
            },
            methods: {
                sel_candy: function (item) {
                    this.res.push({t: item.t, u: item.u});
                },
                del_candy: function (n) {
                    this.res.splice(n, 1);
                }
            }
        });
        vmain.$watch('uname', function (nv) {
            $.ajax({
                type: "GET",
                url: w.u1,
                dataType: "json",
                data: {
                    name: nv
                },
                success: function (msg) {
                    vmain.candy = msg;
                },
                error: function (msg) {
                    vmain.candy = [];
                    msg = JSON.parse(msg.responseText);
                    w.msgto(msg.msg);
                }
            });
        });
        $('#res_up').click(function () {
            $.ajax({
                type: "POST",
                url: w.u2,
                dataType: "json",
                data: {
                    name: vmain.res,
                    type: 0
                },
                success: function (msg) {
                    location.href = w.u3;
                },
                error: function (msg) {
                    msg = JSON.parse(msg.responseText);
                    w.msgto(msg.msg);
                }
            });
        });
        $('#res_down').click(function () {
            $.ajax({
                type: "POST",
                url: w.u2,
                dataType: "json",
                data: {
                    name: vmain.res,
                    type: 1
                },
                success: function (msg) {
                    location.href = w.u3;
                },
                error: function (msg) {
                    msg = JSON.parse(msg.responseText);
                    w.msgto(msg.msg);
                }
            });
        });
    };
    return {
        init: init
    };
})(jQuery, Vue, window);