var all = (function ($, w, undefined) {
    'use strict';
    var alr = $('#ggly').html();
    var nye = $('#rgly').html();
    var jsr = '经手人：';
    var sj = "时间：";
    var listitem = function (head, data) {
        var str = '<a href="#" class="list-group-item">' +
            '<h4 class="list-group-item-heading">' + head +
            '</h4>';
        for (var i in data) {
            str += '<p class="list-group-item-text">' + data[i] + '</p>';
        }
        str += '</a>';
        return str;
    };
    var itfee = function (fee) {
        var data = [];
        for (var i in fee) {
            var tmp = fee[i];
            if (tmp.code === '1')
                data.push(listitem(alr + '缴费', [jsr + tmp.oper, sj + tmp.fee_time]));
            else
                data.push(listitem(nye + '撤销', [jsr + tmp.oper, sj + tmp.fee_time]));

        }
        return data.join("");
    };
    var itact = function (act) {
        var data = [];
        for (var i in act) {
            var tmp = act[i];
            data.push(listitem(tmp.name, [jsr + tmp.oper, sj + tmp.act_time]));
        }
        return data.join("");
    };
    w.loaddetail = function (id) {
        $.ajax({
            type: "POST",
            url: "/hanbj/data/json_detail",
            data: {
                id: id
            },
            dataType: "json",
            success: function (msg) {
                $('#fee' + id).html(itfee(msg.fee));
                $('#act' + id).html(itact(msg.act));
            },
            error: function (msg) {
                msg = JSON.parse(msg.responseText);
                w.msgto(msg.msg);
            }
        });
    };
    w.detailFormatter = function (index, row) {
        var str = $('#detailV').html();
        str = str.replace('fee', 'fee' + row.id);
        str = str.replace('act', 'act' + row.id);
        return str + "<script" + ">loaddetail(" + row.id + ")<" + "/script>";
    };
    w.codeFormatter = function (value, row) {
        return value === '0' ? '正常' : '注销';
    };
    w.openidFormatter = function (str, row) {
        return !str || str.length === 0 ? '' : '有';
    };
    var init = function () {
        var $table = $('#table');
        $table.bootstrapTable({
            'pageSize': 20,
            formatSearch: function () {
                return '搜索昵称';
            }
        });
    };
    return {
        init: init
    };
})(jQuery, window);