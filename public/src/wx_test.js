var wx_test = (function ($, Vue, w, undefined) {
    'use strict';
    var vmain, vtest, obj;
    var test2017 = {uname: ['素问', '采峦'], test: []};
    var init = function () {
        var answer = test2017;
        vmain = new Vue({
            el: '#objmain',
            data: {
                uname: answer.uname
            },
            methods: {
                sel_name: function (item) {
                    obj = item;
                }
            }
        });
        vtest = new Vue({
            el: '#testmain',
            data: {
                uname: obj,
                test: answer.test
            },
            methods: {}
        });
    };
    return {
        init: init
    };
})(Zepto, Vue, window);