var nav_active = (function ($, w, undefined) {
    'use strict';
    var init = function () {
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