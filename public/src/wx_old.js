var wx_Old = (function ($, w, undefined) {
    'use strict';
    var $weuiAgree, eid, phone;
    var init = function () {
        $weuiAgree = $('#weuiAgree');
        eid = $('#old_eid');
        phone = $('#old_phone');
        $("#oldok").click(function () {
            var weagree = $weuiAgree.prop('checked');
            if (!weagree) {
                w.msgto('请阅读并同意《相关条款》');
                return;
            }
            var old_eid = eid.val();
            var old_phone = phone.val();
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
                    w.msgto(msg.msg);
                }
            });
        });
    };
    return {
        init: init
    };
})(Zepto, window);