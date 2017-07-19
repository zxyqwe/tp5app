var all = (function ($, w, undefined) {
    'use strict';
    var alr = $('#ggly').html();
    var nye = $('#rgly').html();
    w.listitem = function (head, data) {
        var str = '<a href="#" class="list-group-item">' +
            '<h4 class="list-group-item-heading">' + head +
            '</h4>';
        for (var i in data) {
            str += '<p class="list-group-item-text">' + data[i] + '</p>';
        }
        str += '</a>';
        return str;
    };
    w.itfee = function (fee) {
        var data = [];
        for (var i in fee) {
            var tmp = fee[i];
            data.push(listitem(alr + '缴费', ['经手人：' + tmp.oper, "时间：" + tmp.fee_time]));
            if (null !== tmp.unoper) {
                data.push(listitem(nye + '撤销', ['经手人：' + tmp.unoper, "时间：" + tmp.unfee_time]));
            }
        }
        return data.join("");
    };
    w.itact = function (act) {
        var data = [];
        for (var i in act) {
            var tmp = act[i];
            data.push(listitem(tmp.name, ["经手人：" + tmp.oper, "时间：" + tmp.act_time]));
        }
        return data.join("");
    };
    w.loaddetail = function (id) {
        $.ajax({
            type: "POST",
            url: "/hanbj/data/json_detail",
            data: {id: id},
            dataType: "json",
            success: function (msg) {
                $('#fee' + id).html(itfee(msg.fee));
                $('#act' + id).html(itact(msg.act));
            },
            error: function (msg) {
                msg = JSON.parse(msg.responseText);
                alert(msg.msg);
            }
        });
    };
    w.detailFormatter = function (index, row) {
        var str = $('#detailV').html();
        str = str.replace('fee', 'fee' + row.id);
        str = str.replace('act', 'act' + row.id);
        return str + "<script" + ">loaddetail(" + row.id + ")<" + "/script>";
    };
    var init = function () {
        var $table = $('#table');
        $table.bootstrapTable({'pageSize': 20});
    };
    return {
        init: init
    };
})(jQuery, window);