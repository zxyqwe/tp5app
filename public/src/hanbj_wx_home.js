var wx_home = (function ($, Vue, w, undefined) {
    'use strict';
    var $card1, $card0, $cardn, $loading, vact, $activity_button, vvalid, $valid_button, vwork_act_log,
        $work_act_log_button;
    var add_card = function (msg) {
        wx.addCard({
            cardList: [{
                cardId: msg.card_id,
                cardExt: JSON.stringify(msg)
            }],
            success: function (res) {
                w.location.reload(true);
                w.location.search += '&_=' + Date.now();
            },
            fail: function (msg) {
                w.msgto2(msg);
            }
        });
    };
    var open_card = function (msg) {
        wx.openCard({
            cardList: [{
                cardId: msg.card,
                code: msg.code
            }],
            fail: function (msg) {
                weui.alert('请打开“微信-卡包”查看会员卡。');
            },
            complete: function () {
                $loading.addClass('sr-only');
            }
        });
    };
    var ticketapi = function () {
        $cardn.click(function () {
            if (!$loading.hasClass('sr-only')) {
                return;
            }
            $loading.removeClass('sr-only');
            $.ajax({
                type: "GET",
                url: w.u1,
                data: {
                    _ajax: 1,
                },
                dataType: "json",
                success: function (msg) {
                    add_card(msg)
                },
                error: w.msgto,
                complete: function () {
                    $loading.addClass('sr-only');
                }
            });
        });
    };
    var build_act = function (msg) {
        var s = '<p>活动：';
        s += msg.act;
        s += '</p><p>编号：';
        s += msg.uni + '-' + msg.tie;
        s += '</p><p>缴费：';
        var fee = parseInt(msg.fee) + 1;
        var yt = parseInt(msg.yt);
        var cyt = new Date().getFullYear() + 1;
        s += w.repeat_icon('<i class="weui-icon-success act-log-icon"></i>', fee - yt);
        s += w.repeat_icon('<i class="weui-icon-cancel act-log-icon"></i>', cyt - fee);
        s += '</p>';
        return s;
    };
    var get_act = function (work_card_code) {
        w.waitloading();
        $.ajax({
            type: "POST",
            url: w.u2,
            data: {
                _ajax: 1,
                code: work_card_code
            },
            dataType: "json",
            success: function (msg) {
                var s = build_act(msg);
                weui.confirm(s, {
                    title: '扫码结果',
                    buttons: [{
                        label: '取消',
                        type: 'default',
                        onClick: function () {
                        }
                    }, {
                        label: '登记',
                        type: 'primary',
                        onClick: function () {
                            up_act(work_card_code);
                        }
                    }]
                });
            },
            error: w.msgto,
            complete: function () {
                w.cancelloading();
            }
        });
    };
    var up_act = function (work_card_code) {
        w.waitloading();
        $.ajax({
            type: "POST",
            url: w.u3,
            data: {
                _ajax: 1,
                code: work_card_code
            },
            dataType: "json",
            success: function (msg) {
                w.msgok();
            },
            error: w.msgto,
            complete: function () {
                w.cancelloading();
            }
        });
    };
    var work_act_add = function () {
        $('#work_act').click(function () {
            wx.scanQRCode({
                needResult: 1,
                scanType: ["qrCode"],
                success: function (res) {
                    get_act(res.resultStr);
                }
            });
        });
    };
    var load_act_log = function () {
        w.waitloading();
        $.ajax({
            type: "POST",
            url: w.u4,
            dataType: "json",
            data: {
                _ajax: 1,
                offset: vwork_act_log.items.length,
                own: vwork_act_log.own
            },
            success: function (msg) {
                var da = msg.list;
                if (da.length < msg.size) {
                    $work_act_log_button.addClass('sr-only');
                }
                vwork_act_log.items.push.apply(vwork_act_log.items, da);
                vwork_act_log.name = msg.name;
            },
            error: w.msgto,
            complete: function () {
                w.cancelloading();
            }
        });
    };
    var work_act_log = function () {
        vwork_act_log = new Vue({
            el: '#wx_work_act_log',
            data: {
                own: false,
                name: '',
                items: []
            },
            ready: function () {
            }
        });
        vwork_act_log.$watch('own', function () {
            vwork_act_log.items = [];
            load_act_log();
        });
        $work_act_log_button = $('#wx_work_act_log_load');
        $work_act_log_button.click(load_act_log);
        load_act_log();
    };
    var bindclick = function () {
        if (w.worker === '1') {
            $('#workarea').removeClass('sr-only');
            work_act_add();
        }
        w.$status.removeClass('sr-only');
        $loading = w.$status.children('i');
        $card0.click(function () {
            if (!$loading.hasClass('sr-only')) {
                return;
            }
            $loading.removeClass('sr-only');
            $.ajax({
                type: "GET",
                url: w.u5,
                data: {
                    _ajax: 1,
                },
                dataType: "json",
                success: function (msg) {
                    w.location.reload(true);
                    w.location.search += '&_=' + Date.now();
                },
                error: w.msgto,
                complete: function () {
                    $loading.addClass('sr-only');
                }
            });
        });
        $card1.click(function () {
            if (!$loading.hasClass('sr-only')) {
                return;
            }
            $loading.removeClass('sr-only');
            $.ajax({
                type: "GET",
                url: w.u6,
                data: {
                    _ajax: 1,
                },
                dataType: "json",
                success: function (msg) {
                    open_card(msg);
                },
                error: w.msgto
            });
        });
        ticketapi();
    };
    var load_act = function () {
        w.waitloading();
        $.ajax({
            type: "POST",
            url: w.u8,
            dataType: "json",
            data: {
                _ajax: 1,
                offset: vact.items.length
            },
            success: function (msg) {
                var da = msg.list;
                for (var i in da) {
                    if (da[i].type === "1") {
                        da[i].type = "志愿者";
                    } else {
                        da[i].type = "";
                    }
                }
                if (da.length < msg.size) {
                    $activity_button.addClass('sr-only');
                }
                vact.items.push.apply(vact.items, da);
            },
            error: w.msgto,
            complete: function () {
                w.cancelloading();
            }
        });
    };
    var load_valid = function () {
        w.waitloading();
        $.ajax({
            type: "POST",
            url: w.u9,
            dataType: "json",
            data: {
                _ajax: 1,
                offset: vvalid.items.length
            },
            success: function (msg) {
                var da = msg.list;
                if (da.length < msg.size) {
                    $valid_button.addClass('sr-only');
                }
                vvalid.items.push.apply(vvalid.items, da);
                vvalid.real_year = msg.real_year;
                vvalid.real_year_str = msg.real_year_str;
                vvalid.fee_status = msg.fee_status;
            },
            error: w.msgto,
            complete: function () {
                w.cancelloading();
            }
        });
    };
    var bonus = function () {
        $('#renew_bonus').click(function () {
            w.waitloading();
            $.ajax({
                type: "GET",
                url: w.u10,
                data: {
                    _ajax: 1,
                },
                dataType: "json",
                success: function (msg) {
                    w.msgok();
                    w.home();
                },
                error: w.msgto,
                complete: function () {
                    w.cancelloading();
                }
            });
        });
    };
    var activity = function () {
        vact = new Vue({
            el: '#wx_activity',
            data: {
                items: []
            },
            ready: function () {
            }
        });
        $activity_button = $('#wx_activity_load');
        $activity_button.click(load_act);
        load_act();
    };
    var valid = function () {
        vvalid = new Vue({
            el: '#wx_valid',
            data: {
                items: [],
                cur_year: new Date().getFullYear(),
                real_year: 0,
                real_year_str: "",
                fee_status: false
            },
            ready: function () {
            }
        });
        $valid_button = $('#wx_valid_load');
        $valid_button.click(load_valid);
        load_valid();
    };
    var valid_fee = function () {
        var ft = $('#pick_fee .weui-cell__ft');
        var fm = $('#fee_money');
        var sel_value;
        w.waitloading();
        $.ajax({
            type: "GET",
            url: w.u11,
            data: {
                _ajax: 1,
            },
            dataType: "json",
            success: function (msg) {
                $('#pick_fee').click(function () {
                    weui.picker(msg, {
                        defaultValue: [0],
                        onConfirm: function (result) {
                            result = result[0];
                            ft.html(result.label);
                            fm.html(result.fee);
                            sel_value = result.value;
                        }
                    });
                });
            },
            error: w.msgto,
            complete: function () {
                w.cancelloading();
            }
        });
        $('#order').click(function () {
            var year = fm.html();
            if (year < 15) {
                w.msgto2('请选择年数');
                return;
            }
            w.waitloading();
            $.ajax({
                type: "POST",
                url: w.u12,
                dataType: "json",
                data: {
                    _ajax: 1,
                    type: 1,
                    opt: sel_value
                },
                success: function (msg) {
                    msg.success = function (res) {
                        w.msgok();
                        w.location.reload(true);
                        w.location.search += '&_=' + Date.now();
                    };
                    msg.fail = function (msg) {
                        w.msgto2(msg.errMsg);
                    };
                    wx.chooseWXPay(msg);
                },
                error: w.msgto,
                complete: function () {
                    w.cancelloading();
                }
            });
        });
    };
    var change_item = function (name, url, type) {
        var str = '<div class="weui-cells weui-cells_form" style="margin-top: 0"><div class="weui-cell">' +
            '<div class="weui-cell__hd"><label class="weui-label">旧' + type +
            '</label></div><div class="weui-cell__bd">' + name +
            '</div></div><div class="weui-cell"><div class="weui-cell__hd"><label class="weui-label">新' + type +
            '</label></div><div class="weui-cell__bd"><textarea style="background-color: yellow" class="weui-textarea" ' +
            'placeholder="请输入" rows="3">' + name + '</textarea></div></div></div>';
        return weui.confirm(str, function () {
            var val = $('.weui-dialog textarea').val();
            if (val === name) {
                return false;
            }
            w.waitloading();
            $.ajax({
                type: "POST",
                url: url,
                dataType: "json",
                data: {
                    _ajax: 1,
                    old: name,
                    new: val
                },
                success: function (msg) {
                    w.msgok();
                    w.location.reload(true);
                    w.location.search += '&_=' + Date.now();
                },
                error: w.msgto,
                complete: function () {
                    w.cancelloading();
                }
            });
        }, {
            title: '修改' + type
        });
    };
    var pref = function (name) {
        change_item(name, w.u14 + '?action=pref', '兴趣爱好');
    };
    var web = function (name) {
        change_item(name, w.u14 + '?action=web_name', '常用网名');
    };
    var select_birth = function (name) {
        weui.datePicker({
            start: 1949,
            end: new Date(),
            defaultValue: name.split('-'),
            onConfirm: function (result) {
                var ret = new Date();
                ret.setFullYear(result[0], result[1] - 1, result[2]);
                change_birth(name, ret.toISOString().slice(0, 10));
            },
            id: 'select_birth_datePicker'
        });
    };
    var change_birth = function (name, result) {
        var str = '<div class="weui-cells weui-cells_form" style="margin-top: 0"><div class="weui-cell">' +
            '<div class="weui-cell__hd"><label class="weui-label">旧生日' +
            '</label></div><div class="weui-cell__bd">' + name +
            '</div></div><div class="weui-cell"><div class="weui-cell__hd"><label class="weui-label">新生日' +
            '</label></div><div class="weui-cell__bd"><div class="weui-cell__bd">' + result +
            '</div></div></div></div>';
        return weui.confirm(str, function () {
            if (result === name) {
                return false;
            }
            w.waitloading();
            $.ajax({
                type: "POST",
                url: w.u14 + '?action=birth',
                dataType: "json",
                data: {
                    _ajax: 1,
                    old: name,
                    new: result
                },
                success: function (msg) {
                    w.msgok();
                    w.location.reload(true);
                    w.location.search += '&_=' + Date.now();
                },
                error: w.msgto,
                complete: function () {
                    w.cancelloading();
                }
            });
        }, {
            title: '修改生日'
        });
    };
    var trans_location_func = function () {
        var trans_location_dom = $('#trans_location');
        var name = trans_location_dom.html();
        if (name.length !== 6) {
            return;
        }
        name = [name.substr(0, 2), name.substr(2, 2), name.substr(4, 2)];
        for (var idx in w.regions) {
            idx = w.regions[idx];
            if (idx.value !== name[0]) {
                continue;
            }
            for (var idx2 in idx.children) {
                idx2 = idx.children[idx2];
                if (idx2.value !== name[1]) {
                    continue;
                }
                for (var idx3 in idx2.children) {
                    idx3 = idx2.children[idx3];
                    if (idx3.value !== name[2]) {
                        continue;
                    }
                    console.log(idx);
                    console.log(idx2);
                    console.log(idx3);
                    trans_location_dom.html(idx.label + idx2.label + idx3.label);
                }
            }
        }
    };
    var change_location = function (old_num, new_num, new_label) {
        var trans_location_dom = $('#trans_location');
        var name = trans_location_dom.html();
        var str = '<div class="weui-cells weui-cells_form" style="margin-top: 0"><div class="weui-cell">' +
            '<div class="weui-cell__hd"><label class="weui-label">旧地区' +
            '</label></div><div class="weui-cell__bd">' + name +
            '</div></div><div class="weui-cell"><div class="weui-cell__hd"><label class="weui-label">新地区' +
            '</label></div><div class="weui-cell__bd"><div class="weui-cell__bd">' + new_label +
            '</div></div></div></div>';
        return weui.confirm(str, function () {
            if (old_num === new_num) {
                return false;
            }
            w.waitloading();
            $.ajax({
                type: "POST",
                url: w.u14 + '?action=location',
                dataType: "json",
                data: {
                    _ajax: 1,
                    old: old_num,
                    new: new_num
                },
                success: function (msg) {
                    w.msgok();
                    w.location.reload(true);
                    w.location.search += '&_=' + Date.now();
                },
                error: w.msgto,
                complete: function () {
                    w.cancelloading();
                }
            });
        }, {
            title: '修改地区'
        });
    };
    var select_location = function (old_name) {
        var name = old_name.toString();
        if (name.length === 6) {
            name = [name.substr(0, 2), name.substr(2, 2), name.substr(4, 2)];
        } else {
            name = ['11', '01', '08'];
        }
        weui.picker(regions, {
            depth: 3,
            defaultValue: name,
            onConfirm: function (result) {
                var value = result[0].value + result[1].value + result[2].value;
                var label = result[0].label + result[1].label + result[2].label;
                console.log(value);
                console.log(label);
                change_location(old_name, value, label);
            },
            id: 'select_location_picker'
        });
    };
    var init = function () {
        $cardn = $("#card-1");
        $card0 = $("#card0");
        $card1 = $("#card1");
        bindclick();
        trans_location_func();
    };
    return {
        init: init,
        activity: activity,
        valid: valid,
        bonus: bonus,
        valid_fee: valid_fee,
        work_act_log: work_act_log,
        pref: pref,
        web: web,
        select_birth: select_birth,
        select_location: select_location,
        build: function () {
            get_act('416521837905');
        }
    };
})(Zepto, Vue, window);

var wx_prom = (function ($, w, undefined) {
    'use strict';
    var vmain;
    var vue_init = function (prom) {
        vmain = new Vue({
            el: '#vmain',
            data: {
                hist: prom
            },
            methods: {
                imgurl: function (u) {
                    return '/static/prom/' + u;
                },
                toggle_rto: function (item) {
                    if (item.rto === 2) {
                        item.rto = 20;
                    } else {
                        item.rto = 2;
                    }
                }
            }
        });
    };
    var init = function () {
        $('#tempid').click(function () {
            w.waitloading();
            $.ajax({
                type: "GET",
                url: w.u7,
                data: {
                    _ajax: 1,
                },
                dataType: "json",
                success: function (msg) {
                    var temp = msg.temp;
                    weui.alert('<p>临时身份码</p><p>有效期：30分钟</p><p class="temp-text">' + temp + '</p>');
                },
                error: w.msgto,
                complete: function () {
                    w.cancelloading();
                }
            });
        });
        w.waitloading();
        $.ajax({
            type: "GET",
            url: w.u19,
            data: {
                _ajax: 1,
            },
            dataType: "json",
            success: function (msg) {
                for (var i in msg) {
                    msg[i].rto = 2;
                }
                vue_init(msg);
            },
            error: w.msgto,
            complete: function () {
                w.cancelloading();
            }
        });
    };
    return {
        init: init
    };
})(Zepto, window);


var wx_hist = (function ($, Vue, w, undefined) {
    'use strict';
    var vmain;
    var vue_init = function () {
        vmain = new Vue({
            el: '#vmain',
            data: {
                hist: []
            },
            methods: {
                trans: w.fame_img,
                trans2: w.grade
            }
        });
    };
    var init = function () {
        w.waitloading();
        $.ajax({
            type: "GET",
            url: w.u17,
            data: {
                _ajax: 1,
            },
            dataType: "json",
            success: function (msg) {
                vue_init();
                vmain.hist = msg.hist;
                $('#vmain').removeClass('sr-only');
            },
            error: w.msgto,
            complete: function () {
                w.cancelloading();
            }
        });
    };
    return {
        init: init
    };
})(Zepto, Vue, window);