var home = (function ($, w, undefined) {
    'use strict';
    var ticketapi = function () {
        $("#card-1").click(function () {
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
                    $("#card1").click(function () {
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
                    ticketapi();
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
        $("#card0").click(function () {
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
                }
            });
        });
        jsapi();
    };
    return {
        init: init
    };
})(Zepto, window);