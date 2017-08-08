var wx_home = (function ($, Vue, w, undefined) {
    'use strict';
    var $card1, $card0, $cardn, $loading, vact, $activity_button, vvalid, $valid_button, $cardDialog, $workerDialog,
        work_card_code;
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
    var bindclick = function () {
        if (w.worker === 1) {
            $('#workarea').removeClass('sr-only');
        }
        w.$status.removeClass('sr-only');
        $loading = w.$status.children('i');
        $('.js_dialog').on('click', '.weui-dialog__btn_primary', function () {
            $(this).parents('.js_dialog').fadeOut(200);
        });
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
                    wx.openCard({
                        cardList: [{
                            cardId: msg.card,
                            code: msg.code
                        }],
                        fail: function (msg) {
                            $cardDialog.fadeIn(200);
                        },
                        complete: function () {
                            $loading.addClass('sr-only');
                        }
                    });
                },
                error: function (msg) {
                    msg = JSON.parse(msg.responseText);
                    w.msgto(msg.msg);
                }
            });
        });
        $('#work_act').click(function () {
            wx.scanQRCode({
                needResult: 1,
                scanType: ["qrCode"],
                success: function (res) {
                    w.waitloading();
                    work_card_code = res.resultStr;
                    $.ajax({
                        type: "POST",
                        url: "/hanbj/work/json_card",
                        data: {code: work_card_code},
                        dataType: "json",
                        success: function (msg) {
                            $('#workuni').html(msg.uni);
                            $('#worktie').html(msg.tie);
                            var s = '<i class="';
                            if (msg.fee < new Date().getFullYear()) {
                                s += 'weui-icon-cancel';
                            } else {
                                s += 'weui-icon-success';
                            }
                            s += '"></i>' + msg.fee;
                            $('#workfee').html(s);
                            $workerDialog.fadeIn(200);
                        },
                        error: function (msg) {
                            msg = JSON.parse(msg.responseText);
                            w.msgto(msg.msg);
                        },
                        complete: function () {
                            w.cancelloading();
                        }
                    });
                }
            });
        });
        $('#workerDialog').on('click', '.dialog__btn_reg', function () {
            $(this).parents('.js_dialog').fadeOut(200);
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
    var init = function () {
        $cardn = $("#card-1");
        $card0 = $("#card0");
        $card1 = $("#card1");
        $cardDialog = $('#cardDialog');
        $workerDialog = $('#workerDialog');
        bindclick();
    };
    return {
        init: init,
        activity: activity,
        valid: valid,
        bonus: bonus
    };
})(Zepto, Vue, window);