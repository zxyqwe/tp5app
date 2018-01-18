var wx_Old = (function ($, w, undefined) {
    'use strict';
    var $weuiAgree, eid, phone;
    var init = function () {
        $weuiAgree = $('#weuiAgree');
        eid = $('#old_eid');
        phone = $('#old_phone');
        $("#oldok").click(function () {
            var weagree = $weuiAgree.prop('checked');
            if (!weagree) {
                w.msgto2('请阅读并同意《相关条款》');
                return;
            }
            var old_eid = eid.val();
            if (old_eid.length !== 6) {
                w.msgto2('身份证输入的不是6位？当前是' + old_eid.length + '位！');
                return;
            }
            var old_phone = phone.val();
            w.waitloading();
            $.ajax({
                type: "POST",
                url: w.u13,
                data: {
                    phone: old_phone,
                    eid: old_eid
                },
                dataType: "json",
                success: function (msg) {
                    w.msgok();
                    setTimeout(function () {
                        WeixinJSBridge.call('closeWindow');
                        w.location.href = w.u16;
                        w.location.search += '&_=' + Date.now();
                    }, 1000);
                },
                error: function (msg) {
                    w.msgto(msg);
                },
                complete: function () {
                    w.cancelloading();
                }
            });
        });
    };
    return {
        init: init
    };
})(Zepto, window);

var wx_New = (function ($, Vue, w, undefined) {
    'use strict';
    var $weuiAgree, vmain, un_data;
    var get_res = function (res) {
        res = res[0].label + res[1].label + res[2].label;
        vmain.unique_name = res;
    };
    var picker = function () {
        weui.picker(un_data, {
            container: 'body',
            onChange: function (result) {
                get_res(result);
            },
            onConfirm: function (result) {
                get_res(result);
            }
        });
    };
    var submit = function () {
        w.waitloading();
        $.ajax({
            type: "POST",
            url: w.u15,
            data: {
                uni: vmain.unique_name,
                tie: vmain.tie
            },
            dataType: "json",
            success: function (msg) {
                w.msgok();
                setTimeout(function () {
                    WeixinJSBridge.call('closeWindow');
                    w.location.href = w.u16;
                    w.location.search += '&_=' + Date.now();
                }, 1000);
            },
            error: function (msg) {
                w.msgto(msg);
            },
            complete: function () {
                w.cancelloading();
            }
        });
    };
    var assm_data = function (data) {
        var adata = [], c0, c1, s0, s1;
        for (var i in data) {
            var tmp = data[i];
            if (tmp[0] !== s0) {
                if (c0 !== undefined) {
                    if (c1 !== undefined) {
                        c0.children.push(c1);
                    }
                    adata.push(c0);
                }
                c0 = {
                    label: tmp[0],
                    children: []
                };
                s0 = tmp[0];
                s1 = undefined;
                c1 = undefined;
            }
            if (tmp[1] !== s1) {
                if (c1 !== undefined) {
                    c0.children.push(c1);
                }
                c1 = {
                    label: tmp[1],
                    children: []
                };
                s1 = tmp[1];
            }
            c1.children.push({
                label: tmp[2]
            });
        }
        c0.children.push(c1);
        adata.push(c0);
        return adata;
    };
    var get_unused = function () {
        w.waitloading();
        $.ajax({
            type: "GET",
            url: w.u15,
            dataType: "json",
            success: function (msg) {
                un_data = assm_data(msg.data);
            },
            error: function (msg) {
                w.msgto(msg);
            },
            complete: function () {
                w.cancelloading();
            }
        });
        vue_init();
    };
    var vue_init = function () {
        vmain = new Vue({
            el: '#vmain',
            data: {
                unique_name: '',
                tie: ''
            },
            methods: {
                uni: function () {
                    picker();
                },
                sub: function () {
                    var weagree = $weuiAgree.prop('checked');
                    if (!weagree) {
                        w.msgto2('请阅读并同意《相关条款》');
                        return;
                    }
                    if (this.unique_name.length === 0 || this.tie.length === 0) {
                        return;
                    }
                    submit();
                }
            }
        });
    };
    var init = function () {
        $weuiAgree = $('#weuiAgree');
        get_unused();
    };
    return {
        init: init
    };
})(Zepto, Vue, window);

var wx_test = (function ($, Vue, w, undefined) {
    'use strict';
    var vmain, testdata, v_slide, step;
    var waitSlide = function () {
        var v = vmain.user_a * step;
        if (v === 0) {
            v = 1;
        }
        v_slide = weui.slider('#slider', {
            step: step,
            defaultValue: v,
            onChange: function (percent) {
                vmain.setPos(percent);
            }
        });
    };
    var init = function () {
        testdata = $('#testdata').html();
        testdata = JSON.parse(testdata);
        if (undefined === testdata.ans.sel) {
            testdata.ans.sel = [];
        }
        if (undefined === testdata.ans.sel_add) {
            testdata.ans.sel_add = [];
        }
        vmain = new Vue({
            el: '#objmain',
            data: {
                uname: testdata.uname,
                name: testdata.name,
                test: testdata.test,//问卷
                ans: testdata.ans.sel,//答案
                ans_add: testdata.ans.sel_add,//答案补充
                cur_q: '',//问题
                cur_a: undefined,//选项
                cur_i: 0,//第几题
                user_a: undefined,//用户的选择
                user_add: '',
                slidepos: 0,
                max_score: 10
            },
            computed: {
                user_add_len: function () {
                    return this.user_add.length;
                },
                well_score: function () {
                    return this.slidepos >= 0.6 * this.max_score && this.slidepos < this.max_score;
                },
                next_cond: function () {
                    return !this.cur_a
                        || this.well_score
                        || (this.slidepos > 0 && this.user_add_len >= 15);
                }
            },
            methods: {
                setPos: function (percent) {
                    this.slidepos = Math.round(percent / step);
                },
                next: function (n) {
                    this.ans[this.cur_i] = this.slidepos;
                    if (this.user_add_len >= 15) {
                        this.ans_add[this.cur_i] = this.user_add;
                        this.user_add = '';
                    }
                    this.cur_i = this.cur_i + n;
                    var i_tmp = this.cur_i;
                    if (i_tmp >= this.test.length || i_tmp < 0) {
                        this.cur_i = i_tmp - n;
                        return;
                    }

                    var tmp = this.test[i_tmp];
                    this.cur_q = tmp.q;
                    this.cur_a = tmp.a;
                    if (undefined === this.cur_a) {
                        this.slidepos = 0;
                        return;
                    }
                    this.user_a = this.ans[i_tmp];
                    this.user_add = this.ans_add[i_tmp];
                    if (undefined === this.user_a) {
                        this.user_a = 0;
                    }
                    if (undefined === this.user_add) {
                        this.user_add = '';
                    }
                    this.slidepos = this.user_a;
                    step = 10;
                    this.max_score = 10;
                    if (tmp.s) {
                        this.max_score = tmp.s;
                        step = 100 / tmp.s;
                    }

                    requestAnimationFrame(waitSlide);
                },
                up: function () {
                    w.waitloading();
                    this.next(0);
                    var sum = 0;
                    var i = this.ans.length;
                    while (i--) {
                        sum += this.ans[i];
                    }
                    if (sum === 100) {
                        w.msgto2('全满分');
                        w.cancelloading();
                        return;
                    }
                    $.ajax({
                        type: "POST",
                        url: "/hanbj/wxtest/up",
                        dataType: "json",
                        data: {
                            ans: {
                                sel: this.ans,
                                sel_add: this.ans_add
                            },
                            obj: this.uname.split(' - ')[0]
                        },
                        success: function (msg) {
                            w.msgok('提交成功');
                            setTimeout(function () {
                                WeixinJSBridge.call('closeWindow');
                            }, 1000);
                        },
                        error: function (msg) {
                            w.msgto(msg);
                        },
                        complete: function () {
                            w.cancelloading();
                        }
                    });
                }
            },
            ready: function () {
                this.next(0);
            }
        });
    };
    return {
        init: init
    };
})(Zepto, Vue, window);