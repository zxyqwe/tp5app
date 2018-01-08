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
            try {
                msg = JSON.parse(msg.responseText);
                msg = msg.msg;
            } catch (err) {
                msg = msg.readyState + '-' + msg.status + '-' + msg.responseText;
            }
            w.msgto2(msg);
        };
        w.msgto2 = function (msg) {
            $alert_html.html(msg);
            $alert_msg.modal('show');
        };
        w.waitloading = function (msg) {
            if (undefined === msg) {
                msg = '数据加载中';
            }
            waitingDialog.show(msg);
        };
        w.cancelloading = function () {
            waitingDialog.hide();
        };
    };
    return {
        init: init
    };
})(jQuery, window);

var nav_active = (function ($, w, undefined) {
    'use strict';
    var dict = function () {
        var base = '/hanbj/';
        var write = 'write/';
        var daily = "daily/";
        var pub = 'pub/';
        var index = 'index/';
        var system = 'system/';
        var analysis='analysis/';
        w.u9 = base + pub + 'json_fame';
        w.u11 = base + pub + 'json_login';
        w.u5 = base + daily + 'actlog';
        w.u3 = base + daily + 'feelog';
        w.u7 = base + daily + 'json_detail';
        w.u13 = base + daily + 'list_act';
        w.u6 = base + daily + 'bonus_add';
        w.u12 = base + system + 'runlog/data/';
        w.u8 = base + system + 'json_tree';
        w.u14 = base + analysis + 'json_birth';
        w.u16 = base + analysis + 'json_brief';
        w.u17 = base + analysis + 'json_group';
        w.u1 = base + write + 'fee_search';
        w.u2 = base + write + 'fee_add';
        w.u4 = base + write + 'vol_add';
        w.u10 = base + write + 'fame_add';
        w.u15 = base + write + 'json_create';
    };
    var init = function () {
        dict();
        var nav = w.nav;
        if (nav === undefined)
            return;
        while (nav.length > 0) {
            $('#' + nav).addClass('active');
            nav = nav.substring(0, nav.lastIndexOf('_'));
        }
    };
    return {
        init: init
    };
})(jQuery, window);