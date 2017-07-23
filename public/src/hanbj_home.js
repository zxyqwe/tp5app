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
    };
    return {
        init: init
    };
})(Zepto, window);