var login = (function ($, w, undefined) {
    'use strict';
    var $mm = $('#mm');
    var init = function () {
        $('#capt_img').click(function () {
            $('#capt_img').attr('src', '/captcha.html?' + new Date());
        });
        $("#login").click(function () {
            var mm = $mm.val();
            mm = CryptoJS.SHA1(mm).toString();
            mm += w.nonStr;
            mm = CryptoJS.SHA1(mm).toString();
            $mm.val(mm);
            var d = $('#form').serializeArray();
            $.ajax({
                type: "POST",
                url: "/hanbj/dataopen/json_login",
                data: d,
                dataType: "json",
                success: function (msg) {
                    location.reload(true);
                },
                error: function (msg) {
                    msg = JSON.parse(msg.responseText);
                    w.msgto(msg.msg);
                    $('#capt_img').attr('src', '/captcha.html?' + new Date());
                    $('#mm').val('');
                    $('#capt').val('');
                }
            });
        });
    };
    return {
        init: init
    };
})(jQuery, window);