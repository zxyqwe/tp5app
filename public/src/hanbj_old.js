var Old = (function ($, w, undefined) {
    'use strict';
    var $toast = $('#toast');
    var $old_msg = $('#old_msg');
    var msgto = function (data) {
        old_msg.html(data);
        if ($toast.css('display') != 'none') return;

        $toast.fadeIn(100);
        setTimeout(function () {
            $toast.fadeOut(100);
        }, 2000);
    };
    var init = function () {
        $("#oldok").click(function () {
            var weuiAgree = $('#weuiAgree').get(0).checked;
            if (!weuiAgree) {
                msgto('阅读并同意《相关条款》');
                return;
            }
            var old_eid = $('#old_eid').val();
            var old_phone = $('#old_phone').val();
            $.ajax({
                type: "POST",
                url: "/hanbj/mobile/json_old",
                data: {
                    phone: old_phone,
                    eid: old_eid
                },
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
})(jQuery, window);