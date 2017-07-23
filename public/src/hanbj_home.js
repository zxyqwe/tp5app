var home = (function ($, w, undefined) {
    'use strict';
    var $toast, $old_msg;
    var msgto = function (data) {
        $old_msg.html(data);
        var tdis = $toast.css('display');
        if ('none' !== tdis)
            return;

        $toast.fadeIn(100);
        setTimeout(function () {
            $toast.fadeOut(100);
        }, 2000);
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
                    jsApiList: ['openCard']
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
                        }
                    );
                });
                wx.error(function (res) {
                    console.log(res);
                });
            },
            error: function (msg) {
                msg = JSON.parse(msg.responseText);
                msgto(msg.msg);
            }
        })
        ;
    };
    var init = function () {
        $toast = $('#toast');
        $old_msg = $('#old_msg');
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
                    msgto(msg.msg);
                }
            });
        });
        jsapi();
    };
    return {
        init: init
    };
})(Zepto, window);