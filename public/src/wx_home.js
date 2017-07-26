var wx_home = (function ($, w, undefined) {
    'use strict';
    var $card1, $card0, $cardn, $loading;
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
                            cardExt: msg
                        }],
                        success: function (res) {
                            w.location.href = location.href.split('#')[0];
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
                    w.location.href = location.href.split('#')[0];
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
        ticketapi();
    };
    var activity = function () {
        var vact = new Vue({
            el: '#wx_activity',
            ready: function () {
                $('wx_activity_load').click(function () {
                    $.ajax({
                        type: "GET",
                        url: "/hanbj/mobile/json_activity",
                        dataType: "json",
                        success: function (msg) {
                            var ori = vact.data.items;
                            ori.push.apply(ori, msg);
                        },
                        error: function (msg) {
                            msg = JSON.parse(msg.responseText);
                            w.msgto(msg.msg);
                        }
                    });
                });
            },
            data: {
                items: {}
            }
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
        activity: activity
    };
})(Zepto, window);