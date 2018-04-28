var all_mem = (function ($, w, undefined) {
    'use strict';
    var alr, nye, jsr = '经手人：', sj = "时间：", jf = "积分：", rz = "状态：", yrz = "已积分", wrz = "未积分", vmain, $table;
    var listitem = function (head, data) {
        var str = '<a href="#" class="list-group-item">' +
            '<h4 class="list-group-item-heading">' + head +
            '</h4>';
        for (var i in data) {
            str += '<p class="list-group-item-text">' + data[i] + '</p>';
        }
        str += '</a>';
        return str;
    };
    var itfee = function (fee) {
        var data = [];
        for (var i in fee) {
            var tmp = fee[i];
            if (tmp.code === '1')
                data.push(listitem(alr + '缴费', [jsr + tmp.oper, sj + tmp.fee_time, jf + tmp.bonus, rz + (tmp.up === '1' ? yrz : wrz)]));
            else
                data.push(listitem(nye + '撤销', [jsr + tmp.oper, sj + tmp.fee_time, jf + tmp.bonus, rz + (tmp.up === '1' ? yrz : wrz)]));

        }
        return data.join("");
    };
    var itact = function (act) {
        var data = [];
        for (var i in act) {
            var tmp = act[i];
            data.push(listitem(tmp.name, [jsr + tmp.oper, sj + tmp.act_time, jf + tmp.bonus, rz + (tmp.up === '1' ? yrz : wrz)]));
        }
        return data.join("");
    };
    var itfame = function (fame) {
        var data = [];
        for (var i in fame) {
            var tmp = fame[i];
            data.push(listitem(w.grade(tmp.grade), ['第 ' + tmp.year + ' 届：' + tmp.label]));
        }
        return data.join("");
    };
    w.wxParams = function (params) {
        params.up = vmain.up;
        return params;
    };
    var build_vue = function (refresh) {
        var le = [], u = [];
        for (var i in Array.from(Array(5).keys())) {
            le.push({v: i, n: w.mem_code(i)});
            u.push(i)
        }
        vmain = new Vue({
            el: '#toolbar',
            data: {
                up: u,
                level: le
            }
        });
        vmain.$watch('up', function (nv) {
            $table.bootstrapTable('refresh');
        });
    };
    var init = function () {
        alr = $('#ggly').html();
        nye = $('#rgly').html();
        build_vue();
        w.loaddetail = function (id) {
            w.waitloading();
            $.ajax({
                type: "POST",
                url: w.u7,
                data: {
                    id: id
                },
                dataType: "json",
                success: function (msg) {
                    $('#fee' + id).html(itfee(msg.fee));
                    $('#act' + id).html(itact(msg.act));
                    $('#fame' + id).html(itfame(msg.fame));
                },
                error: w.msgto,
                complete: function () {
                    w.cancelloading();
                }
            });
        };
        w.detailFormatter = function (index, row) {
            var str = $('#detailV').html();
            str = str.replace('fee', 'fee' + row.id);
            str = str.replace('act', 'act' + row.id);
            str = str.replace('fame', 'fame' + row.id);
            return str + "<script" + ">loaddetail(" + row.id + ")<" + "/script>";
        };
        w.codeFormatter = function (value, row) {
            return w.mem_code(value);
        };
        w.openidFormatter = function (str, row) {
            return !str || str.length === 0 ? '' : '有';
        };
        $table = $('#table');
        $table.bootstrapTable({
            'pageSize': 20,
            formatSearch: function () {
                return '搜索昵称或会员编号';
            }
        });
    };
    return {
        init: init
    };
})(jQuery, window);


var baselog = (function ($, Vue, w, undefined) {
    'use strict';
    var $wxup, $table, t_data = {up: false};
    var refresh = function (tmp_data) {
        t_data = tmp_data;
        $table.bootstrapTable('refresh');
    };
    var build_vue = function (refresh) {
        var vmain = new Vue({
            el: '#simp_form',
            data: {
                uname: '',
                up: false,
                act: [],
                act_res: ''
            },
            methods: {
                get_res: function () {
                    return {
                        uname: this.uname,
                        up: this.up,
                        act: this.act_res
                    }
                }
            }
        });
        vmain.$watch('uname', function (nv) {
            refresh(this.get_res());
        });
        vmain.$watch('up', function (nv) {
            refresh(this.get_res());
        });
        vmain.$watch('act_res', function (nv) {
            refresh(this.get_res());
        });
        vmain.$watch('act', function (nv) {
            $('select').selectpicker({size: false});
        });
        w.waitloading();
        $.ajax({
            type: "GET",
            url: w.u13,
            dataType: "json",
            success: function (msg) {
                vmain.act = msg;
            },
            error: function (jqXHR, msg, ethrow) {
                vmain.act = [];
                w.msgto(jqXHR, msg, ethrow);
            },
            complete: function () {
                w.cancelloading();
            }
        });
    };
    var init = function (uptype, build_v) {
        w.wxFormatter = function (value, row) {
            return value === '0' ? '未更新' : '';
        };
        w.wxParams = function (params) {
            for (var x in t_data) {
                params[x] = t_data[x];
            }
            return params;
        };
        w.codeFormatter = function (value, row) {
            return w.mem_code(value);
        };
        if (undefined === build_v) {
            build_vue(refresh);
        } else {
            build_v(refresh);
        }
        $table = $('#table');
        $table.bootstrapTable({
            'pageSize': 20
        });
        $wxup = $('#wxup');
        $wxup.click(function () {
            if (!$wxup.hasClass('sr-only')) {
                $wxup.addClass('sr-only');
                w.waitloading();
            }
            $.ajax({
                type: "POST",
                url: w.u6,
                data: {type: uptype},
                dataType: "json",
                success: function (msg) {
                    refresh(t_data);
                    if (msg.c > 0) {
                        $wxup.click();
                    } else {
                        w.cancelloading();
                        setTimeout(function () {
                            $wxup.removeClass('sr-only');
                        }, 500);
                    }
                },
                error: function (jqXHR, msg, ethrow) {
                    w.cancelloading();
                    w.msgto(jqXHR, msg, ethrow);
                }
            });
        });
    };
    return {
        init: init
    };
})(jQuery, Vue, window);


var feelog = (function ($, Vue, w, undefined) {
    'use strict';
    var alr, nye;
    var build_vue = function (refresh) {
        var vmain = new Vue({
            el: '#simp_form',
            data: {
                uname: '',
                up: false
            },
            methods: {
                get_res: function () {
                    return {
                        uname: this.uname,
                        up: this.up
                    }
                }
            }
        });
        vmain.$watch('uname', function (nv) {
            refresh(this.get_res());
        });
        vmain.$watch('up', function (nv) {
            refresh(this.get_res());
        });
    };
    var init = function () {
        w.code2Formatter = function (value, row) {
            return value === '1' ? alr : nye;
        };
        alr = $('#ggly').html() + '缴费';
        nye = $('#rgly').html() + '撤销';
        baselog.init(0, build_vue);
    };
    return {
        init: init
    };
})(jQuery, Vue, window);


var card = (function ($, w, undefined) {
    'use strict';
    var init = function () {
        var $table = $('#table');
        $table.bootstrapTable({
            'pageSize': 20,
            formatSearch: function () {
                return '搜索昵称或会员编号';
            }
        });
        w.codeFormatter = function (value, row) {
            return w.mem_code(value);
        };
        w.cardFormatter = function (value, row) {
            return value === '0' ? '' : '激活';
        };
    };
    return {
        init: init
    };
})(jQuery, window);


var order = (function ($, w, undefined) {
    'use strict';
    var $table;
    var fee_handle = function (n, v) {
        return n + ' → ' + (parseInt(v) + 1) + '年';
    };
    var handle = function (y, v) {
        switch (y) {
            case '1':
                return fee_handle('会费', v);
        }
    };
    var init = function () {
        w.codeFormatter = function (value, row) {
            return w.mem_code(value);
        };
        $table = $('#table');
        $table.bootstrapTable({
            'pageSize': 20,
            responseHandler: function (res) {
                var msg = res.rows;
                for (var i in msg) {
                    var y = msg[i].y;
                    var v = msg[i].v;
                    msg[i].y = handle(y, v);
                }
                return res;
            }
        });
    };
    return {
        init: init
    };
})(jQuery, window);


var volunteer = (function ($, Vue, w, undefined) {
    'use strict';
    var vmain;
    var init = function () {
        vmain = new Vue({
            el: '#body',
            data: {
                uname: '',
                candy: [],
                res: []
            },
            methods: {
                sel_candy: function (item) {
                    this.res.push({t: item.t, u: item.u});
                },
                del_candy: function (n) {
                    this.res.splice(n, 1);
                }
            }
        });
        vmain.$watch('uname', function (nv) {
            w.waitloading();
            $.ajax({
                type: "POST",
                url: w.u1,
                dataType: "json",
                data: {
                    name: nv
                },
                success: function (msg) {
                    vmain.candy = msg;
                },
                error: function (jqXHR, msg, ethrow) {
                    vmain.candy = [];
                    w.msgto(jqXHR, msg, ethrow);
                },
                complete: function () {
                    w.cancelloading();
                }
            });
        });
        $('#res_up').click(function () {
            w.waitloading();
            $.ajax({
                type: "POST",
                url: w.u4,
                dataType: "json",
                data: {
                    name: vmain.res
                },
                success: function (msg) {
                    w.location.href = w.u5;
                },
                error: w.msgto,
                complete: function () {
                    w.cancelloading();
                }
            });
        });
    };
    return {
        init: init
    };
})(jQuery, Vue, window);

var fee = (function ($, Vue, w, undefined) {
    'use strict';
    var vmain;
    var init = function () {
        vmain = new Vue({
            el: '#body',
            data: {
                uname: '',
                candy: [],
                res: []
            },
            methods: {
                sel_candy: function (item) {
                    this.res.push({t: item.t, u: item.u});
                },
                del_candy: function (n) {
                    this.res.splice(n, 1);
                }
            }
        });
        vmain.$watch('uname', function (nv) {
            w.waitloading();
            $.ajax({
                type: "POST",
                url: w.u1,
                dataType: "json",
                data: {
                    name: nv
                },
                success: function (msg) {
                    vmain.candy = msg;
                },
                error: function (jqXHR, msg, ethrow) {
                    vmain.candy = [];
                    w.msgto(jqXHR, msg, ethrow);
                },
                complete: function () {
                    w.cancelloading();
                }
            });
        });
        $('#res_up').click(function () {
            w.waitloading();
            $.ajax({
                type: "POST",
                url: w.u2,
                dataType: "json",
                data: {
                    name: vmain.res,
                    type: 0
                },
                success: function (msg) {
                    w.location.href = w.u3;
                },
                error: w.msgto,
                complete: function () {
                    w.cancelloading();
                }
            });
        });
        $('#res_down').click(function () {
            w.waitloading();
            $.ajax({
                type: "POST",
                url: w.u2,
                dataType: "json",
                data: {
                    name: vmain.res,
                    type: 1
                },
                success: function (msg) {
                    w.location.href = w.u3;
                },
                error: w.msgto,
                complete: function () {
                    w.cancelloading();
                }
            });
        });
    };
    return {
        init: init
    };
})(jQuery, Vue, window);


var tlog = (function ($, w, undefined) {
    'use strict';
    var $tree, $cont, $title, auto_handle, tenc_len = 0;
    var cont = function (c, t) {
        $cont.html(c);
        $title.html(t);
    };
    var cancel_up = function () {
        tenc_len = 0;
        cont('', '');
        clearInterval(auto_handle);
    };
    var auto_up = function (par, chi) {
        up(par, chi);
        auto_handle = setInterval(function () {
            up(par, chi);
        }, 600000);
    };
    var up = function (par, chi) {
        w.waitloading();
        $.ajax({
            type: "POST",
            url: w.u12 + tenc_len,
            data: {
                par: par,
                chi: chi
            },
            dataType: "json",
            success: function (msg) {
                var mtext = msg.text;
                if (false === mtext) {
                    return;
                }
                tenc_len = msg.len;
                cont($cont.html() + mtext, par + ' - ' + chi);
            },
            error: w.msgto,
            complete: function () {
                w.cancelloading();
            }
        });
    };
    var init = function () {
        $tree = $('#dir_tree');
        $cont = $('#log_cont');
        $title = $('#log_title');
        var data = $('#dir_data').html();
        data = JSON.parse(data);
        $tree.treeview({
            levels: 1,
            data: data,
            onNodeSelected: function (event, data) {
                cancel_up();
                if (undefined !== data.nodes) {
                    $tree.treeview('expandNode', [data.nodeId, {silent: true}]);
                    return;
                }
                var parent = $tree.treeview('getParent', data);
                $tree.treeview('expandNode', [parent.nodeId, {silent: true}]);
                auto_up(parent.text, data.text);
            },
            onNodeUnselected: function (event, data) {
                cancel_up();
                if (undefined !== data.nodes) {
                    $tree.treeview('collapseNode', [data.nodeId, {silent: true}]);
                }
            }
        });
    };
    return {
        init: init
    };
})(jQuery, window);


var fame = (function ($, Vue, w, undefined) {
    'use strict';
    var vmain;
    var init = function () {
        vmain = new Vue({
            el: '#fame',
            data: {
                fames: []
            },
            methods: {
                fame_img: w.fame_img,
                fame_name: w.grade
            }
        });
        w.waitloading();
        $.ajax({
            type: "GET",
            url: w.u9,
            dataType: "json",
            success: function (msg) {
                vmain.fames = msg;
            },
            error: function (jqXHR, msg, ethrow) {
                vmain.fames = [];
                w.msgto(jqXHR, msg, ethrow);
            },
            complete: function () {
                w.cancelloading();
            }
        });
    };
    var initlog = function () {
        Vue.filter('grade_code', function (n) {
            return w.grade('' + n);
        });
        vmain = new Vue({
            el: '#body',
            data: {
                uname: '',
                year: 0,
                grade: 0,
                labelname: '中枢',
                candy: [],
                res: [],
                labellist: ['中枢', '会员部', '会员中心', '外联部', '外事中心', '外事部', '宣传部', '宣传中心',
                    '活动部', '活动中心', '人力部', '人力资源与会员事务部', '秘书处', '办公室', '产业中心', '社推部',
                    '新媒体运营部', '交流联络部', '公共关系部', '换届选举监委会'].sort()
            },
            methods: {
                sel_candy: function (item) {
                    this.res.push({t: item.t, u: item.u});
                },
                del_candy: function (n) {
                    this.res.splice(n, 1);
                }
            }
        });
        vmain.$watch('uname', function (nv) {
            w.waitloading();
            $.ajax({
                type: "POST",
                url: w.u1,
                dataType: "json",
                data: {
                    name: nv
                },
                success: function (msg) {
                    vmain.candy = msg;
                },
                error: function (jqXHR, msg, ethrow) {
                    vmain.candy = [];
                    w.msgto(jqXHR, msg, ethrow);
                },
                complete: function () {
                    w.cancelloading();
                }
            });
        });
        $('#res_up').click(function () {
            w.waitloading();
            $.ajax({
                type: "POST",
                url: w.u10,
                dataType: "json",
                data: {
                    name: vmain.res,
                    year: vmain.year,
                    grade: vmain.grade,
                    label: vmain.labelname
                },
                success: function (msg) {
                    vmain.res = [];
                },
                error: w.msgto,
                complete: function () {
                    w.cancelloading();
                }
            });
        });
    };
    var fameori = function () {
        w.code2Formatter = function (value, row) {
            return w.grade(value);
        };
        w.codeFormatter = function (value, row) {
            return w.mem_code(value);
        };
        var $table = $('#table');
        $table.bootstrapTable({
            formatSearch: function () {
                return '搜索昵称或会员编号';
            }
        });
    };
    return {
        init: init,
        initlog: initlog,
        fameori: fameori
    };
})(jQuery, Vue, window);

var test = (function ($, Vue, w, undefined) {
    'use strict';
    var init = function () {
        var msg = $('#data').html();
        msg = JSON.parse(msg);
        var trans = function (item) {
            return item + '-' + msg.trn[item];
        };
        var vmain = new Vue({
            el: '#body',
            data: {
                obj: msg.obj,
                mis: msg.mis,
                rto: msg.rto
            },
            methods: {
                trans: function (item) {
                    return trans(item);
                }
            },
            ready: function () {
                w.codeFormatter = function (value, row) {
                    return trans(value);
                };
                $('#table1').bootstrapTable({
                    data: msg.avg
                });
                $('#table2').bootstrapTable({
                    data: msg.cmt
                });
            }
        });
    };
    return {
        init: init
    };
})(jQuery, Vue, window);


var brief = (function ($, w, undefined) {
    'use strict';
    var ret, catalog_choice = [], catalog_set = {},
        catalog_set_male = {}, catalog_set_female = {}, catalog_set_ung = {}, gender = {g0: 0, g1: 0, g2: 0},
        dist_set = {};
    var get_catalog = function (y, g) {
        catalog_choice.push(y);
        if (undefined === catalog_set[y]) {
            catalog_set[y] = 0;
            catalog_set_male[y] = 0;
            catalog_set_female[y] = 0;
            catalog_set_ung[y] = 0;
        }
        if (g === 0) {
            catalog_set_male[y]++;
        } else if (g === 1) {
            catalog_set_female[y]++;
        } else {
            catalog_set_ung[y]++;
        }
    };
    var get_catalog_ret = function () {
        var myChart = echarts.init(document.getElementById('catalog'));
        catalog_choice = Array.from(new Set(catalog_choice));
        catalog_choice = catalog_choice.sort();
        var a = [], b = [], c = [];
        for (var i in catalog_choice) {
            var k = catalog_choice[i];
            a.push(catalog_set_male[k]);
            b.push(catalog_set_female[k]);
            c.push(catalog_set_ung[k]);
        }
        var option = {
            title: {
                text: '会员入会时间分布'
            },
            tooltip: {
                trigger: 'item',
                formatter: "{b}年: {a} {c}人"
            },
            legend: {
                top: '40',
                data: ['男', '女', '未知']
            },
            xAxis: {
                data: catalog_choice
            },
            yAxis: {},
            series: [{
                name: '男',
                type: 'bar',
                stack: '总量',
                data: a
            }, {
                name: '女',
                type: 'bar',
                stack: '总量',
                data: b
            }, {
                name: '未知',
                type: 'bar',
                stack: '总量',
                data: c
            }]
        };
        myChart.setOption(option);
    };
    var get_gender = function (g) {
        gender['g' + g]++;
    };
    var get_gender_year = function () {
        var myChart = echarts.init(document.getElementById('gender'));
        var option = {
            title: {
                text: '实名会员男女比例'
            },
            tooltip: {
                trigger: 'item',
                formatter: "{a} <br/>{b}: {c} ({d}%)"
            },
            legend: {
                top: '60',
                orient: 'vertical',
                x: 'left',
                data: ['男', '女', '未知']
            },
            series: [
                {
                    name: '实名会员分布',
                    type: 'pie',
                    radius: '50%',
                    data: [
                        {value: gender.g0, name: '男'},
                        {value: gender.g1, name: '女'},
                        {value: gender.g2, name: '未知'}
                    ]
                }
            ]
        };
        myChart.setOption(option);
    };
    var get_dist = function (g) {
        if (undefined === dist_set[g]) {
            dist_set[g] = 0;
        }
        dist_set[g]++;
    };
    var get_dist_year = function () {
        var leg = [], ser = [];
        for (var i in dist_set) {
            leg.push(w.mem_code(i));
            ser.push({name: w.mem_code(i), value: dist_set[i]});
        }
        var myChart = echarts.init(document.getElementById('dist'));
        var option = {
            title: {
                text: '编号状态分布'
            },
            tooltip: {
                trigger: 'item',
                formatter: "{a} <br/>{b}: {c} ({d}%)"
            },
            legend: {
                top: '60',
                orient: 'vertical',
                x: 'left',
                data: leg
            },
            series: [
                {
                    name: '编号分布',
                    type: 'pie',
                    radius: '50%',
                    data: ser
                }
            ]
        };
        myChart.setOption(option);
    };
    var get_ret = function () {
        w.waitloading();
        $.ajax({
            type: "GET",
            url: w.u16,
            dataType: "json",
            success: function (msg) {
                ret = msg;
                for (var i in ret) {
                    var k = ret[i];
                    if (['0', '4'].includes(k.code)) {
                        get_gender(k.gender);
                        get_catalog(k.year_time, k.gender);
                    }
                    get_dist(k.code);
                }
                get_dist_year();
                get_gender_year();
                get_catalog_ret();
            },
            error: w.msgto,
            complete: function () {
                w.cancelloading();
            }
        });
    };
    var init = function () {
        get_ret();
    };
    return {
        init: init
    };
})(jQuery, window);


var group = (function ($, w, undefined) {
    'use strict';
    var ret, join = {}, join1 = [], join2 = [], cata = {}, cata1 = [], cata2 = [], gender = {}, gender1 = [],
        gender2 = [];
    var get_gender = function (j, c) {
        gender1.push(j);
        gender2.push(c);
        if (undefined === gender[j + c]) {
            gender[j + c] = 0;
        }
        gender[j + c]++;
    };
    var get_gender_ret = function () {
        var myChart = echarts.init(document.getElementById('gender'));
        gender1 = Array.from(new Set(gender1));
        gender2 = Array.from(new Set(gender2));
        var series = [];
        for (var i in gender2) {
            i = gender2[i];
            var s = [];
            for (var j in gender1) {
                j = gender1[j];
                if (undefined === gender[j + i]) {
                    s.push(0);
                } else {
                    s.push(gender[j + i]);
                }
            }
            series.push({
                name: i,
                type: 'bar',
                stack: '总量',
                data: s
            });
        }
        var option = {
            title: {
                text: '实名会员性别分布'
            },
            tooltip: {
                trigger: 'item',
                formatter: "{b}字组: {a} {c}个"
            },
            legend: {
                top: '40',
                data: gender2
            },
            xAxis: {
                data: gender1
            },
            yAxis: {},
            series: series
        };
        myChart.setOption(option);
    };
    var get_cata = function (j, c) {
        cata1.push(j);
        cata2.push(c);
        if (undefined === cata[j + c]) {
            cata[j + c] = 0;
        }
        cata[j + c]++;
    };
    var get_cata_ret = function () {
        var myChart = echarts.init(document.getElementById('catalog'));
        cata1 = Array.from(new Set(cata1));
        cata2 = Array.from(new Set(cata2));
        var series = [];
        for (var i in cata2) {
            i = cata2[i];
            var s = [];
            for (var j in cata1) {
                j = cata1[j];
                if (undefined === cata[j + i]) {
                    s.push(0);
                } else {
                    s.push(cata[j + i]);
                }
            }
            series.push({
                name: i,
                type: 'bar',
                stack: '总量',
                data: s
            });
        }
        var option = {
            title: {
                text: '所有会员入会时间'
            },
            tooltip: {
                trigger: 'item',
                formatter: "{b}字组: {a}年 {c}个"
            },
            legend: {
                top: '40',
                data: cata2
            },
            xAxis: {
                data: cata1
            },
            yAxis: {},
            series: series
        };
        myChart.setOption(option);
    };
    var get_join = function (j, c) {
        c = w.mem_code(c);
        join1.push(j);
        join2.push(c);
        if (undefined === join[j + c]) {
            join[j + c] = 0;
        }
        join[j + c]++;
    };
    var get_join_ret = function () {
        var myChart = echarts.init(document.getElementById('join'));
        join1 = Array.from(new Set(join1));
        join2 = Array.from(new Set(join2));
        var series = [];
        for (var i in join2) {
            i = join2[i];
            var s = [];
            for (var j in join1) {
                j = join1[j];
                if (undefined === join[j + i]) {
                    s.push(0);
                } else {
                    s.push(join[j + i]);
                }
            }
            series.push({
                name: i,
                type: 'bar',
                stack: '总量',
                data: s
            });
        }
        var option = {
            title: {
                text: '编号状态分布'
            },
            tooltip: {
                trigger: 'item',
                formatter: "{b}字组: {a} {c}个"
            },
            legend: {
                top: '40',
                data: join2
            },
            xAxis: {
                data: join1
            },
            yAxis: {},
            series: series
        };
        myChart.setOption(option);
    };
    var get_ret = function () {
        w.waitloading();
        $.ajax({
            type: "GET",
            url: w.u17,
            dataType: "json",
            success: function (msg) {
                ret = msg;
                for (var i in ret) {
                    var k = ret[i];
                    if (['0', '4'].includes(k.code)) {
                        get_cata(k.u, k.year_time);
                        if (k.code === '0') {
                            get_gender(k.u, k.gender);
                        }
                    }
                    get_join(k.u, k.code)
                }
                get_cata_ret();
                get_gender_ret();
                get_join_ret();
            },
            error: w.msgto,
            complete: function () {
                w.cancelloading();
            }
        });
    };
    var init = function () {
        get_ret();
    };
    return {
        init: init
    };
})(jQuery, window);


var birth = (function ($, w, undefined) {
    'use strict';
    var ret, year_set = {}, year_choice = [], year_set_male = {}, year_set_female = {}, year_set_ung = {},
        birthday_set = {}, default_year = new Date().getFullYear(), join = {}, join1 = [], join2 = [], cata = {},
        cata1 = [], cata2 = [];
    var trans_year = function (y) {
        var tmp = default_year - y;
        if (tmp < 18) {
            return '未成年';
        } else if (tmp < 25) {
            return '18~24岁';
        } else if (tmp < 30) {
            return '25~29岁'
        } else {
            return '30岁以上';
        }
    };
    var get_catalog = function (j, c) {
        c = trans_year(c);
        cata1.push(j);
        cata2.push(c);
        if (undefined === cata[j + c]) {
            cata[j + c] = 0;
        }
        cata[j + c]++;
    };
    var get_catalog_ret = function () {
        var myChart = echarts.init(document.getElementById('catalog'));
        cata1 = Array.from(new Set(cata1));
        cata2 = Array.from(new Set(cata2));
        var series = [];
        for (var i in cata2) {
            i = cata2[i];
            var s = [];
            for (var j in cata1) {
                j = cata1[j];
                if (undefined === cata[j + i]) {
                    s.push(0);
                } else {
                    s.push(cata[j + i]);
                }
            }
            series.push({
                name: i,
                type: 'bar',
                stack: '总量',
                data: s
            });
        }
        var option = {
            title: {
                text: '实名会员字组分布'
            },
            tooltip: {
                trigger: 'item',
                formatter: "{b}字组: {a} {c}个"
            },
            legend: {
                top: '40',
                data: cata2
            },
            xAxis: {
                data: cata1
            },
            yAxis: {},
            series: series
        };
        myChart.setOption(option);
    };
    var get_join = function (j, c) {
        c = trans_year(c);
        join1.push(j);
        join2.push(c);
        if (undefined === join[j + c]) {
            join[j + c] = 0;
        }
        join[j + c]++;
    };
    var get_join_ret = function () {
        var myChart = echarts.init(document.getElementById('join'));
        join1 = Array.from(new Set(join1));
        join1 = join1.sort();
        join2 = Array.from(new Set(join2));
        var series = [];
        for (var i in join2) {
            i = join2[i];
            var s = [];
            for (var j in join1) {
                j = join1[j];
                if (undefined === join[j + i]) {
                    s.push(0);
                } else {
                    s.push(join[j + i]);
                }
            }
            series.push({
                name: i,
                type: 'bar',
                stack: '总量',
                data: s
            });
        }
        var option = {
            title: {
                text: '实名会员入会时间分布'
            },
            tooltip: {
                trigger: 'item',
                formatter: "{b}年: {a} {c}个"
            },
            legend: {
                top: '40',
                data: join2
            },
            xAxis: {
                data: join1
            },
            yAxis: {},
            series: series
        };
        myChart.setOption(option);
    };
    var get_year = function (y, g) {
        year_choice.push(y);
        if (undefined === year_set[y]) {
            year_set[y] = 0;
            year_set_male[y] = 0;
            year_set_female[y] = 0;
            year_set_ung[y] = 0;
        }
        if (g === 0) {
            year_set_male[y]++;
        } else if (g === 1) {
            year_set_female[y]++;
        } else {
            year_set_ung[y]++;
        }
    };
    var get_year_ret = function () {
        var myChart = echarts.init(document.getElementById('year'));
        year_choice = Array.from(new Set(year_choice));
        year_choice = year_choice.sort();
        var a = [], b = [], c = [];
        for (var i in year_choice) {
            var k = year_choice[i];
            a.push(year_set_male[k]);
            b.push(year_set_female[k]);
            c.push(year_set_ung[k]);
        }
        var option = {
            title: {
                text: '实名会员年龄分布'
            },
            tooltip: {
                trigger: 'item',
                formatter: "{b}年: {a} {c}人"
            },
            legend: {
                top: '40',
                data: ['男', '女', '未知']
            },
            xAxis: {
                data: year_choice
            },
            yAxis: {},
            series: [{
                name: '男',
                type: 'bar',
                stack: '总量',
                data: a
            }, {
                name: '女',
                type: 'bar',
                stack: '总量',
                data: b
            }, {
                name: '未知',
                type: 'bar',
                stack: '总量',
                data: c
            }]
        };
        myChart.setOption(option);
    };
    var get_birthday = function (m, d) {
        var s = default_year + '-' + m + '-' + d;
        if (undefined === birthday_set[s]) {
            birthday_set[s] = 0;
        }
        birthday_set[s]++;
    };
    var get_birthday_ret = function () {
        var myChart = echarts.init(document.getElementById('birthday'));
        var vdata = [];
        for (var i in birthday_set) {
            vdata.push([
                i,
                Math.floor(birthday_set[i])
            ]);
        }
        var sli = vdata.sort(function (a, b) {
            return b[1] - a[1];
        }).slice(0, 12);
        var option = {
            backgroundColor: '#404a59',
            title: {
                top: 30,
                text: '当前会员生日分布',
                left: 'center',
                textStyle: {
                    color: '#fff'
                }
            },
            tooltip: {
                trigger: 'item',
                formatter: function (params, ticket, callback) {
                    var v = params.value;
                    var s = v[0].split('-');
                    return s[1] + '月' + s[2] + '日 共' + v[1] + '人';
                }
            },
            legend: {
                top: '30',
                left: '100',
                data: ['生日', 'Top 12'],
                textStyle: {
                    color: '#fff'
                }
            },
            calendar: [{
                top: 100,
                left: 'center',
                range: [default_year + '-01-01', default_year + '-06-30'],
                splitLine: {
                    show: true,
                    lineStyle: {
                        color: '#000',
                        width: 4,
                        type: 'solid'
                    }
                },
                yearLabel: {
                    formatter: '上半年',
                    textStyle: {
                        color: '#fff'
                    }
                },
                itemStyle: {
                    normal: {
                        color: '#323c48',
                        borderWidth: 1,
                        borderColor: '#111'
                    }
                }
            }, {
                top: 300,
                left: 'center',
                range: [default_year + '-07-01', default_year + '-12-31'],
                splitLine: {
                    show: true,
                    lineStyle: {
                        color: '#000',
                        width: 4,
                        type: 'solid'
                    }
                },
                yearLabel: {
                    formatter: '下半年',
                    textStyle: {
                        color: '#fff'
                    }
                },
                itemStyle: {
                    normal: {
                        color: '#323c48',
                        borderWidth: 1,
                        borderColor: '#111'
                    }
                }
            }],
            series: [
                {
                    name: '生日',
                    type: 'scatter',
                    coordinateSystem: 'calendar',
                    data: vdata,
                    symbolSize: function (val) {
                        return val[1] * 3;
                    },
                    itemStyle: {
                        normal: {
                            color: '#ddb926'
                        }
                    }
                },
                {
                    name: 'Top 12',
                    type: 'effectScatter',
                    coordinateSystem: 'calendar',
                    data: sli,
                    symbolSize: function (val) {
                        return val[1] * 3;
                    },
                    showEffectOn: 'render',
                    rippleEffect: {
                        brushType: 'stroke'
                    },
                    hoverAnimation: true,
                    itemStyle: {
                        normal: {
                            color: '#f4e925',
                            shadowBlur: 10,
                            shadowColor: '#333'
                        }
                    },
                    zlevel: 1
                },
                {
                    name: '生日',
                    type: 'scatter',
                    coordinateSystem: 'calendar',
                    calendarIndex: 1,
                    data: vdata,
                    symbolSize: function (val) {
                        return val[1] * 3;
                    },
                    itemStyle: {
                        normal: {
                            color: '#ddb926'
                        }
                    }
                },
                {
                    name: 'Top 12',
                    type: 'effectScatter',
                    coordinateSystem: 'calendar',
                    calendarIndex: 1,
                    data: sli,
                    symbolSize: function (val) {
                        return val[1] * 3;
                    },
                    showEffectOn: 'render',
                    rippleEffect: {
                        brushType: 'stroke'
                    },
                    hoverAnimation: true,
                    itemStyle: {
                        normal: {
                            color: '#f4e925',
                            shadowBlur: 10,
                            shadowColor: '#333'
                        }
                    },
                    zlevel: 1
                }
            ]
        };
        myChart.setOption(option);
    };
    var get_ret = function () {
        w.waitloading();
        $.ajax({
            type: "GET",
            url: w.u14,
            dataType: "json",
            success: function (msg) {
                ret = msg;
                for (var i in ret) {
                    var k = ret[i];
                    var e = k.eid;
                    var g = k.gender;
                    if (e) {
                        var y = e.substring(0, 4);
                        var m = e.substring(4, 6);
                        var d = e.substring(6, 8);
                        get_year(y, g);
                        get_catalog(k.u, y);
                        get_join(k.year_time, y);
                        get_birthday(m, d);
                    }
                }
                get_year_ret();
                get_catalog_ret();
                get_join_ret();
                get_birthday_ret();
            },
            error: w.msgto,
            complete: function () {
                w.cancelloading();
            }
        });
    };
    var init = function () {
        get_ret();
    };
    return {
        init: init
    };
})(jQuery, window);


var create = (function ($, Vue, w, undefined) {
    'use strict';
    var vmain;
    var clear = function () {
        vmain.s = false;
        vmain.uname = '';
        vmain.candy = [];
        vmain.uni = '';
        vmain.tie = '';
        vmain.gender = '';
        vmain.phone = '';
        vmain.QQ = '';
        vmain.eid = '';
        vmain.rn = '';
        vmain.mail = '';
    };
    var init = function () {
        vmain = new Vue({
            el: '#body',
            data: {
                uname: '',
                candy: [],
                s: false,
                uni: '',
                tie: '',
                gender: '',
                phone: '',
                QQ: '',
                eid: '',
                rn: '',
                mail: ''
            },
            methods: {
                sel_candy: function (item) {
                    clear();
                    this.uni = item.u;
                    this.tie = item.t;
                    this.s = true;
                },
                clr: function () {
                    clear();
                },
                sub: function () {
                    w.waitloading();
                    $.ajax({
                        type: "POST",
                        url: w.u15,
                        dataType: "json",
                        data: {
                            uni: vmain.uni,
                            tie: vmain.tie,
                            gender: vmain.gender,
                            phone: vmain.phone,
                            QQ: vmain.QQ,
                            eid: vmain.eid,
                            rn: vmain.rn,
                            mail: vmain.mail
                        },
                        success: function (msg) {
                            clear();
                        },
                        error: w.msgto,
                        complete: function () {
                            w.cancelloading();
                        }
                    });
                }
            }
        });
        vmain.$watch('uname', function (nv) {
            w.waitloading();
            $.ajax({
                type: "GET",
                url: w.u15,
                dataType: "json",
                data: {
                    name: nv
                },
                success: function (msg) {
                    vmain.candy = msg;
                },
                error: function (jqXHR, msg, ethrow) {
                    clear();
                    w.msgto(jqXHR, msg, ethrow);
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
})(jQuery, Vue, window);

var tree = (function ($, w, undefined) {
    'use strict';
    var dragx, dragy, g, scale = 0.185302019;
    var changeScale = function (step) {
        scale *= 1 + step;
        updateG();
    };
    var updateG = function () {
        g.attr("transform", "translate(" + dragx + "," + dragy + ")scale(" + scale + ")");
    };
    var radialPoint = function (x, y) {
        return [(y = +y) * Math.cos(x -= Math.PI / 2), y * Math.sin(x)];
    };
    var init = function () {

        var svg = d3.select("svg"),
            width = +$('#container').width(),
            height = +svg.attr("height");

        dragx = (width / 2 + 40);
        dragy = (height / 2 + 90);
        g = svg.append("g");
        updateG();

        var drag = d3.drag()
            .subject(function () {
                return {x: dragx, y: dragy};
            })
            .on("drag", function () {
                dragx = d3.event.x;
                dragy = d3.event.y;
                updateG();
            });
        svg.call(drag);
        var stratify = d3.stratify()
            .parentId(function (d) {
                return d.m;
            }).id(function (d) {
                return d.u;
            });

        var tree = d3.tree()
            .size([2 * Math.PI, 2400])
            .separation(function (a, b) {
                return (a.parent === b.parent ? 1 : 2) / a.depth;
            });

        d3.json(w.u8, function (error, data) {
            if (error) throw error;
            var real = [{m: '', t: '始祖', u: '汉.梦里水乡', c: 1}];
            var real_par = ['汉.梦里水乡'];
            while (data.length > 0) {
                for (var i = 0; i < data.length; i++) {
                    var tmp = data[i];
                    if (undefined === tmp) {
                        break;
                    }
                    if ($.inArray(tmp.m, real_par) >= 0) {
                        real.push(tmp);
                        real_par.push(tmp.u);
                        data.splice(i, 1);
                        i--;
                        if (i < 0) {
                            i = 0;
                        }
                    }
                }
            }
            var root = tree(stratify(real));

            var link = g.selectAll(".link")
                .data(root.links())
                .enter().append("path")
                .attr("class", "link")
                .attr("d", d3.linkRadial()
                    .angle(function (d) {
                        return d.x;
                    })
                    .radius(function (d) {
                        return d.y;
                    }));

            var node = g.selectAll(".node")
                .data(root.descendants())
                .enter().append("g")
                .attr("class", function (d) {
                    return "node" + (d.children ? " node--internal" : "") + (d.data.c !== '0' ? " node-gone" : "");
                })
                .attr("transform", function (d) {
                    return "translate(" + radialPoint(d.x, d.y) + ")";
                });

            node.append("circle")
                .attr("r", 5);

            node.append("text")
                .attr("dy", "0.31em")
                .attr("x", function (d) {
                    return d.x < Math.PI === !d.children ? 6 : -6;
                })
                .attr("text-anchor", function (d) {
                    return d.x < Math.PI === !d.children ? "start" : "end";
                })
                .attr("transform", function (d) {
                    return "rotate(" + (d.x < Math.PI ? d.x - Math.PI / 2 : d.x + Math.PI / 2) * 180 / Math.PI + ")";
                })
                .text(function (d) {
                    return d.data.u + ':' + d.data.t;
                });
        });

    };
    return {
        init: init,
        change: changeScale
    };
})(jQuery, window);
