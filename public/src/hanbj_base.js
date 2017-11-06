var home = (function ($, w, undefined) {
    'use strict';
    var $btt, $alert_html, $alert_msg;
    var init = function () {
        $btt = $("#back-to-top");
        $alert_html = $('#alert_html');
        $alert_msg = $('#alert_msg');
        $btt.hide();
        $(w).scroll(function () {
            if ($(w).scrollTop() > 100) {
                if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
                    return;
                }
                $("#back-to-top").fadeIn(700);
            }
            else {
                $("#back-to-top").fadeOut(700);
            }
        });
        $btt.click(function () {
            $('html,body').animate({scrollTop: 0}, 1000);
            return false;
        });
        w.msgto = function (msg) {
            $alert_html.html(msg);
            $alert_msg.modal('show');
        };
    };
    var mem_code = function (value) {
        switch (value) {
            case '0':
                return '正常';
            case '1':
                return '注销';
            default:
                return value;
        }
    };
    return {
        init: init,
        mem_code: mem_code
    };
})(jQuery, window);
