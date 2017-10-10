var wx_test = (function ($, Vue, w, undefined) {
    'use strict';
    var vmain, vtest;
    var answer = [];
    var init = function () {
        var testdata = $('#testdata').innerHTML();
        testdata = JSON.parse(testdata);
        vmain = new Vue({
            el: '#objmain',
            data: {
                uname: testdata.uname
            },
            methods: {
                sel_name: function (item) {
                    vtest.uname = item;
                }
            }
        });
        vtest = new Vue({
            el: '#testmain',
            data: {
                uname: '',
                test: answer
            },
            methods: {}
        });
    };
    return {
        init: init
    };
})(Zepto, Vue, window);