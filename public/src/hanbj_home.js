var home = (function ($, w, undefined) {
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
    var jsapi = function () {
        $.ajax({
            type: "GET",
            url: "/hanbj/mobile/json_wx?url=" + encodeURIComponent(location.href.split('#')[0]),
            dataType: "json",
            success: function (msg) {
                wx.config({
                    appId: msg.api,
                    timestamp: msg.timestamp,
                    nonceStr: msg.nonce,
                    signature: msg.signature,
                    jsApiList: ['openCard', 'addCard']
                });
                wx.ready(function () {
                    bindclick(msg);
                });
                wx.error(function (res) {
                    console.log(res);
                });
            },
            error: function (msg) {
                msg = JSON.parse(msg.responseText);
                w.msgto(msg.msg);
            }
        });
    };
    var bindclick = function (msg) {
        w.$status.removeClass('sr-only');
        $loading = w.$status.children('i');
        $card0.click(function () {
            if (!$loading.hasClass('sr-only')) {
                return;
            }
            $loading.removeClass('sr-only');
            $.ajax({
                type: "GET",
                url: "/hanbj/mobile/json_card",
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
            wx.openCard({
                cardList: [{
                    cardId: msg.card,
                    code: msg.code
                }],
                success: function (res) {
                    console.log(res);
                },
                fail: function (res) {
                    console.log(res);
                },
                complete: function () {
                    $loading.addClass('sr-only');
                }
            });
        });
        ticketapi();
    };
    var init = function () {
        jsapi();
        $cardn = $("#card-1");
        $card0 = $("#card0");
        $card1 = $("#card1");
    };
    return {
        init: init
    };
})(Zepto, window);