var all = (function ($, w, undefined) {
    'use strict';
    var alr = $('#ggly').html();
    var nye = $('#rgly').html();
    var jsr = '经手人：';
    var sj = "时间：";
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
                data.push(listitem(alr + '缴费', [jsr + tmp.oper, sj + tmp.fee_time]));
            else
                data.push(listitem(nye + '撤销', [jsr + tmp.oper, sj + tmp.fee_time]));

        }
        return data.join("");
    };
    var itact = function (act) {
        var data = [];
        for (var i in act) {
            var tmp = act[i];
            data.push(listitem(tmp.name, [jsr + tmp.oper, sj + tmp.act_time]));
        }
        return data.join("");
    };
    var init = function () {
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
                },
                error: function (msg) {
                    msg = JSON.parse(msg.responseText);
                    w.msgto(msg.msg);
                },
                complete: function () {
                    w.cancelloading();
                }
            });
        };
        w.detailFormatter = function (index, row) {
            var str = $('#detailV').html();
            str = str.replace('fee', 'fee' + row.id);
            str = str.replace('act', 'act' + row.id);
            return str + "<script" + ">loaddetail(" + row.id + ")<" + "/script>";
        };
        w.codeFormatter = function (value, row) {
            return home.mem_code(value);
        };
        w.openidFormatter = function (str, row) {
            return !str || str.length === 0 ? '' : '有';
        };
        var $table = $('#table');
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
        w.waitloading();
        $.ajax({
            type: "GET",
            url: w.u13,
            dataType: "json",
            success: function (msg) {
                vmain.act = msg;
            },
            error: function (msg) {
                vmain.act = [];
                msg = JSON.parse(msg.responseText);
                w.msgto(msg.msg);
            },
            complete: function () {
                w.cancelloading();
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
                error: function (msg) {
                    w.cancelloading();
                    msg = JSON.parse(msg.responseText);
                    w.msgto(msg.msg);
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
        w.codeFormatter = function (value, row) {
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
            return home.mem_code(value);
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
                type: "GET",
                url: w.u1,
                dataType: "json",
                data: {
                    name: nv
                },
                success: function (msg) {
                    vmain.candy = msg;
                },
                error: function (msg) {
                    vmain.candy = [];
                    msg = JSON.parse(msg.responseText);
                    w.msgto(msg.msg);
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
                    location.href = w.u5;
                },
                error: function (msg) {
                    msg = JSON.parse(msg.responseText);
                    w.msgto(msg.msg);
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
    var dragx, dragy, g, scale = 1;
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
            .size([2 * Math.PI, 1200])
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
                .attr("r", 2.5);

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
                type: "GET",
                url: w.u1,
                dataType: "json",
                data: {
                    name: nv
                },
                success: function (msg) {
                    vmain.candy = msg;
                },
                error: function (msg) {
                    vmain.candy = [];
                    msg = JSON.parse(msg.responseText);
                    w.msgto(msg.msg);
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
                    location.href = w.u3;
                },
                error: function (msg) {
                    msg = JSON.parse(msg.responseText);
                    w.msgto(msg.msg);
                },
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
                    location.href = w.u3;
                },
                error: function (msg) {
                    msg = JSON.parse(msg.responseText);
                    w.msgto(msg.msg);
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


var tlog = (function ($, w, undefined) {
    'use strict';
    var $tree, $cont, $title, auto_handle, tenc = new TextEncoder('utf-8'), tenc_len = 0;
    var cont = function (c, t) {
        tenc_len = tenc.encode(c).length;
        $cont.html(c);
        $title.html(t);
    };
    var cancel_up = function () {
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
                cont($cont.html() + mtext, par + ' - ' + chi);
            },
            error: function (msg) {
                msg = JSON.parse(msg.responseText);
                w.msgto(msg.msg);
            },
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
