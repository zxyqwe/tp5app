var nav = (function ($, w, undefined) {
    'use strict';
    var init = function () {
        if (nav === undefined)
            return;
        while (nav.length > 0) {
            $('#' + nav).addClass('active');
            nav = nav.substring(0, nav.lastIndexOf('.'));
        }
    };
    return {
        init: init
    };
})(jQuery, window);