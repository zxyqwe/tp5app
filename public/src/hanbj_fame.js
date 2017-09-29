var fame = (function ($, Vue, w, undefined) {
    'use strict';
    var vmain;
    var init = function () {
        vmain = new Vue({
            el: '#fame',
            data: {
                fames: []
            },
            methods: {
                fame_img: function (n) {
                    switch (n) {
                        case '0':
                            return '/static/arrow-up.png';
                        case '1':
                            return '/static/arrow-up.png';
                        case '2':
                            return '/static/arrow-up.png';
                        case '3':
                            return '/static/arrow-up.png';
                        case '4':
                            return '/static/arrow-up.png';
                    }
                },
                fame_name: function (n) {
                    switch (n) {
                        case '0':
                            return '会长';
                        case '1':
                            return '副会长';
                        case '2':
                            return '部长';
                        case '3':
                            return '副部长';
                        case '4':
                            return '干事';
                    }
                }
            }
        });
        $.ajax({
            type: "GET",
            url: "/hanbj/data/json_fame",
            dataType: "json",
            success: function (msg) {
                vmain.fames = msg;
            },
            error: function (msg) {
                vmain.fames = [];
                msg = JSON.parse(msg.responseText);
                w.msgto(msg.msg);
            }
        });
    };
    var initlog = function () {
        vmain = new Vue({
            el: '#body',
            data: {
                uname: '',
                year: 0,
                grade: 0,
                labelname: '',
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
                url: "/hanbj/data/fee_search",
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
                url: "/hanbj/data/fame_add",
                dataType: "json",
                data: {
                    name: vmain.res,
                    year: vmain.year,
                    grade: vmain.grade,
                    label: vmain.labelname
                },
                success: function (msg) {
                    vmain.res = [];
                },
                error: function (msg) {
                    msg = JSON.parse(msg.responseText);
                    w.msgto(msg.msg);
                }
            });
        });
    };
    var fameori = function () {
        var $table = $('#table');
        $table.bootstrapTable();
    };
    return {
        init: init,
        initlog: initlog,
        fameori: fameori
    };
})(jQuery, Vue, window);