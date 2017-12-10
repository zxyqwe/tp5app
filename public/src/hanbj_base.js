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
    var mem_code = function (value) {
        switch (value) {
            case '-1':
                return '空号';
            case '0':
                return '实名会员';
            case '1':
                return '注销';
            case '2':
                return '停机保号';
            case '3':
                return '临时抢号';
            case '4':
                return '会员';
            default:
                return value;
        }
    };
    return {
        init: init,
        mem_code: mem_code
    };
})(jQuery, window);

var nav_active = (function ($, w, undefined) {
    'use strict';
    var dict = function () {
        var base = '/hanbj/';
        var data = 'data/';
        var dataopen = 'dataopen/';
        var index = 'index/';
        w.u1 = base + data + 'fee_search';
        w.u2 = base + data + 'fee_add';
        w.u3 = base + index + 'feelog';
        w.u4 = base + data + 'vol_add';
        w.u5 = base + index + 'actlog';
        w.u6 = base + data + 'bonus_add';
        w.u7 = base + data + 'json_detail';
        w.u8 = base + data + 'json_tree';
        w.u9 = base + dataopen + 'json_fame';
        w.u10 = base + data + 'fame_add';
        w.u11 = base + dataopen + 'json_login';
        w.u12 = base + index + 'runlog/data/';
        w.u13 = base + data + 'list_act';
        w.u14 = base + data + 'json_birth';
        w.u15 = base + data + 'create';
        w.u16 = base + data + 'json_brief';
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