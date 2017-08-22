var wx_home = (function ($, Vue, w, undefined) {
    'use strict';
    var $card1, $card0, $cardn, $loading, vact, $activity_button, vvalid, $valid_button, vwork_act_log,
        $work_act_log_button, $switchCP;
    var add_card = function (msg) {
        wx.addCard({
            cardList: [{
                cardId: msg.card_id,
                cardExt: JSON.stringify(msg)
            }],
            success: function (res) {
                w.location.search = '?g=123';
            },
            fail: function (msg) {
                w.msgto(msg);
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
                url: "/hanbj/mobile/json_addcard",
                dataType: "json",
                success: function (msg) {
                    add_card(msg)
                },
                error: function (msg) {
                    msg = JSON.parse(msg.responseText);
                    w.msgto(msg.msg);
                },
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
        s += msg.uni;
        s += '</p><p>昵称：';
        s += msg.tie;
        s += '</p><p>缴费：';
        s += '<i class="';
        if (msg.fee < new Date().getFullYear()) {
            s += 'weui-icon-cancel';
        } else {
            s += 'weui-icon-success';
        }
        s += '"></i>';
        s += msg.fee;
        s += '</p>';
        return s;
    };
    var get_act = function (work_card_code) {
        w.waitloading();
        $.ajax({
            type: "POST",
            url: "/hanbj/work/json_card",
            data: {code: work_card_code},
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
            error: function (msg) {
                msg = JSON.parse(msg.responseText);
                w.msgto(msg.msg);
            },
            complete: function () {
                w.cancelloading();
            }
        });
    };
    var up_act = function (work_card_code) {
        w.waitloading();
        $.ajax({
            type: "POST",
            url: "/hanbj/work/json_act",
            data: {code: work_card_code},
            dataType: "json",
            success: function (msg) {
                w.msgok();
            },
            error: function (msg) {
                msg = JSON.parse(msg.responseText);
                w.msgto(msg.msg);
            },
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
        $.ajax({
            type: "GET",
            url: "/hanbj/work/act_log",
            dataType: "json",
            data: {
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
            error: function (msg) {
                msg = JSON.parse(msg.responseText);
                w.msgto(msg.msg);
            }
        });
    };
    var work_act_log = function () {
        $switchCP = $('#switchCP');
        $switchCP.click(function () {
            vwork_act_log.own = $switchCP.prop('checked');
            vwork_act_log.items = [];
            load_act_log();
        });
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
        $work_act_log_button = $('#wx_work_act_log_load');
        $work_act_log_button.click(load_act_log);
        load_act_log();
    };
    var bindclick = function () {
        if (w.worker === 1) {
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
                url: "/hanbj/mobile/json_active",
                dataType: "json",
                success: function (msg) {
                    w.location.search = '?g=123';
                },
                error: function (msg) {
                    msg = JSON.parse(msg.responseText);
                    w.msgto(msg.msg);
                },
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
                url: "/hanbj/mobile/json_card",
                dataType: "json",
                success: function (msg) {
                    open_card(msg);
                },
                error: function (msg) {
                    msg = JSON.parse(msg.responseText);
                    w.msgto(msg.msg);
                }
            });
        });
        ticketapi();
    };
    var load_act = function () {
        $.ajax({
            type: "GET",
            url: "/hanbj/wx/json_activity",
            dataType: "json",
            data: {
                offset: vact.items.length
            },
            success: function (msg) {
                var da = msg.list;
                if (da.length < msg.size) {
                    $activity_button.addClass('sr-only');
                }
                vact.items.push.apply(vact.items, da);
            },
            error: function (msg) {
                msg = JSON.parse(msg.responseText);
                w.msgto(msg.msg);
            }
        });
    };
    var load_valid = function () {
        $.ajax({
            type: "GET",
            url: "/hanbj/wx/json_valid",
            dataType: "json",
            data: {
                offset: vvalid.items.length
            },
            success: function (msg) {
                var da = msg.list;
                if (da.length < msg.size) {
                    $valid_button.addClass('sr-only');
                }
                vvalid.items.push.apply(vvalid.items, da);
                vvalid.real_year = msg.real_year;
            },
            error: function (msg) {
                msg = JSON.parse(msg.responseText);
                w.msgto(msg.msg);
            }
        });
    };
    var bonus = function () {
        $('#renew_bonus').click(function () {
            $.ajax({
                type: "GET",
                url: "/hanbj/wx/json_renew",
                dataType: "json",
                success: function (msg) {
                    w.location.search = '?g=123';
                },
                error: function (msg) {
                    msg = JSON.parse(msg.responseText);
                    w.msgto(msg.msg);
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
                real_year: 0
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
            url: "/hanbj/wx/fee_year",
            data: {},
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
            error: function (msg) {
                msg = JSON.parse(msg.responseText);
                w.msgto(msg.msg);
            },
            complete: function () {
                w.cancelloading();
            }
        });
        $('#order').click(function () {
            var year = fm.html();
            if (year < 15) {
                w.msgto('请选择年数');
                return;
            }
            w.waitloading();
            $.ajax({
                type: "POST",
                url: "/hanbj/wx/order",
                dataType: "json",
                data: {
                    type: 1,
                    opt: sel_value
                },
                success: function (msg) {
                    msg.success = function (res) {
                        w.msgok();
                    };
                    msg.fail = function (msg) {
                        w.msgto(msg.errMsg);
                    };
                    wx.chooseWXPay(msg);
                },
                error: function (msg) {
                    msg = JSON.parse(msg.responseText);
                    w.msgto(msg.msg);
                },
                complete: function () {
                    w.cancelloading();
                }
            });
        });
    };
    var init = function () {
        $cardn = $("#card-1");
        $card0 = $("#card0");
        $card1 = $("#card1");
        bindclick();
    };
    return {
        init: init,
        activity: activity,
        valid: valid,
        bonus: bonus,
        valid_fee: valid_fee,
        work_act_log: work_act_log
    };
})(Zepto, Vue, window);