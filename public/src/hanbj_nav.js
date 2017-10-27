var nav_active = (function ($, w, undefined) {
    'use strict';
    var dict = function () {
        var base = '/hanbj/';
        var data = 'data/';
        var index = 'index/';
        w.u1 = base + data + 'fee_search';
        w.u2 = base + data + 'fee_add';
        w.u3 = base + index + 'feelog';
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