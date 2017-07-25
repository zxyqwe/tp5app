var home = (function ($, w, undefined) {
    'use strict';
    var $card1, $card0, $cardn;
    var ticketapi = function () {
        $cardn.click(function () {
            $cardn.addClass('weui-btn_loading');
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
                            location.reload(true);
                        }
                    });
                },
                error: function (msg) {
                    msg = JSON.parse(msg.responseText);
                    w.msgto(msg.msg);
                },
                complete: function () {
                    $cardn.removeClass('weui-btn_loading');
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
                    $card1.click(function () {
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
                            }
                        });
                    });
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
    var init = function () {
        w.$status.css({"display": "block"});
        $cardn = $("#card-1");
        $card0 = $("#card0");
        $card1 = $("#card1");
        $card0.click(function () {
            $card0.addClass('weui-btn_loading');
            $.ajax({
                type: "GET",
                url: "/hanbj/mobile/json_card",
                dataType: "json",
                success: function (msg) {
                    location.reload(true);
                },
                error: function (msg) {
                    msg = JSON.parse(msg.responseText);
                    w.msgto(msg.msg);
                },
                complete: function () {
                    $card0.removeClass('weui-btn_loading');
                }
            });
        });
        jsapi();
        ticketapi();
    };
    return {
        init: init
    };
})(Zepto, window);