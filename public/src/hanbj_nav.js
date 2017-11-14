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
        w.u12 = base + index + 'runlog/data/1';
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