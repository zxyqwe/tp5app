var Old = (function ($, w, undefined) {
    'use strict';
    var init = function () {
        $("#oldok").click(function () {
            var weuiAgree = $('#weuiAgree').get(0).checked;
            if (!weuiAgree) {
                return;
                //ToDo
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
                    alert(msg.msg);
                    //ToDo
                }
            });
        });
    };
    return {
        init: init
    };
})(jQuery, window);