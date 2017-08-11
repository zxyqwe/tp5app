var tree = (function ($, w, undefined) {
    'use strict';
    var init = function () {

        var svg = d3.select("svg"),
            width = +$('#container').width(),
            height = +svg.attr("height"),
            dragx = (width / 2 + 40),
            dragy = (height / 2 + 90),
            g = svg.append("g").attr("transform", "translate(" + dragx + "," + dragy + ")");

        var drag = d3.drag()
            .subject(function () {
                return {x: dragx, y: dragy};
            })
            .on("drag", function () {
                dragx = d3.event.x;
                dragy = d3.event.y;
                d3.select("svg g").attr("transform", "translate(" + dragx + "," + dragy + ")");
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

        d3.json("/hanbj/data/json_tree", function (error, data) {
            if (error) throw error;
            var real = [{m: '', t: '汉.梦里水乡', u: '汉.梦里水乡'}];
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
                    return "node" + (d.children ? " node--internal" : "") + (d.data.c > 0 ? " node-gone" : "");
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
                    return d.data.t;
                });
        });

        function radialPoint(x, y) {
            return [(y = +y) * Math.cos(x -= Math.PI / 2), y * Math.sin(x)];
        }
    };
    return {
        init: init
    };
})(jQuery, window);