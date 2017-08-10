var feelog = (function ($, w, undefined) {
    'use strict';
    var alr, nye, $table, $onlyup, pressedup = false, $wxup;
    w.codeFormatter = function (value, row) {
        return value === '0' ? alr : nye;
    };
    w.wxFormatter = function (value, row) {
        return value === '0' ? '未更新' : '';
    };
    w.wxParams = function (params) {
        params.up = pressedup;
        return params;
    };
    var init = function () {
        alr = $('#ggly').html() + '缴费';
        nye = $('#rgly').html() + '撤销';
        $onlyup = $('#onlyup');
        $onlyup.click(function () {
            var pressed = $onlyup.attr('aria-pressed');
            if (pressed === 'false') {
                $onlyup.html('仅看未更新事件');
                pressedup = true;
                $table.bootstrapTable('refresh');
            } else {
                $onlyup.html('不过滤微信事件');
                pressedup = false;
                $table.bootstrapTable('refresh');
            }
        });
        $table = $('#table');
        $table.bootstrapTable({
            'pageSize': 20
        });
        $wxup = $('#wxup');
        $wxup.click(function () {
            if (!$wxup.hasClass('sr-only')) {
                $wxup.addClass('sr-only');
            }
            $.ajax({
                type: "POST",
                url: "/hanbj/data/bonus_add",
                data: {type: 0},
                dataType: "json",
                success: function (msg) {
                    $table.bootstrapTable('refresh');
                    if (msg.c > 0) {
                        $wxup.click();
                    }
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
})(jQuery, window);