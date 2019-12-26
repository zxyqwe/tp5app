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
            } else {
                $("#back-to-top").fadeOut(700);
            }
        });
        $btt.click(function () {
            $('html,body').animate({scrollTop: 0}, 1000);
            return false;
        });
        w.msgto = function (jqXHR, smsg, ethrow) {
            var msg;
            try {
                msg = JSON.parse(jqXHR.responseText);
                msg = msg.msg;
            } catch (err) {
                msg = jqXHR.readyState + '-' + jqXHR.status + '-' + jqXHR.responseText + '-' + smsg + '-' + ethrow;
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
        var analysis = 'analysis/';
        var develop = 'develop/';
        var fame = 'fame/';
        var system = 'system/';
        w.u9 = base + pub + 'json_fame';
        w.u11 = base + pub + 'json_login';
        w.u5 = base + daily + 'actlog';
        w.u3 = base + daily + 'feelog';
        w.u7 = base + daily + 'json_detail';
        w.u13 = base + daily + 'list_act';
        w.u6 = base + daily + 'bonus_add';
        w.u8 = base + analysis + 'json_tree';
        w.u14 = base + analysis + 'json_birth';
        w.u16 = base + analysis + 'json_brief';
        w.u17 = base + analysis + 'json_group';
        w.u1 = base + write + 'fee_search';
        w.u2 = base + write + 'fee_add';
        w.u4 = base + write + 'vol_add';
        w.u10 = base + fame + 'fame_add';
        w.u15 = base + write + 'json_create';
        w.u18 = base + write + 'json_token';
        w.u19 = base + write + 'edit_prom';
        w.u20 = base + write + 'edit_club';
        w.u12 = base + develop + 'table';
        w.u21 = base + develop + 'tableone';
        w.u22 = base + develop + 'logdata';
        w.u23 = base + system + 'hanbjorderdata';
        w.u24 = base + pub + 'json_vote';
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

window.escapeHtml = function (text) {
    var map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };

    return text.replace(/[&<>"']/g, function (m) {
        return map[m];
    });
};