/*
 Vue.js v0.10.4
 (c) 2014 Evan You
 License: MIT
 */
!function () {
    "use strict";

    function e(t, i, n) {
        var r = e.resolve(t);
        if (null != r) {
            var s = e.modules[r];
            if (!s._resolving && !s.exports) {
                var o = {};
                o.exports = {}, o.client = o.component = !0, s._resolving = !0, s.call(this, o.exports, e.relative(r), o), delete s._resolving, s.exports = o.exports
            }
            return s.exports
        }
    }

    e.modules = {}, e.aliases = {}, e.exts = ["", ".js", ".json", "/index.js", "/index.json"], e.resolve = function (t) {
        "/" === t.charAt(0) && (t = t.slice(1));
        for (var i = 0; 5 > i; i++) {
            var n = t + e.exts[i];
            if (e.modules.hasOwnProperty(n)) return n;
            if (e.aliases.hasOwnProperty(n)) return e.aliases[n]
        }
    }, e.normalize = function (e, t) {
        var i = [];
        if ("." != t.charAt(0)) return t;
        e = e.split("/"), t = t.split("/");
        for (var n = 0; n < t.length; ++n) ".." === t[n] ? e.pop() : "." != t[n] && "" != t[n] && i.push(t[n]);
        return e.concat(i).join("/")
    }, e.register = function (t, i) {
        e.modules[t] = i
    }, e.alias = function (t, i) {
        e.modules.hasOwnProperty(t) && (e.aliases[i] = t)
    }, e.relative = function (t) {
        function i(n) {
            var r = i.resolve(n);
            return e(r, t, n)
        }

        var n = e.normalize(t, "..");
        return i.resolve = function (i) {
            var r = i.charAt(0);
            if ("/" === r) return i.slice(1);
            if ("." === r) return e.normalize(n, i);
            for (var s = t.split("/"), o = s.length; o-- && "deps" !== s[o];);
            return i = s.slice(0, o + 2).join("/") + "/deps/" + i
        }, i.exists = function (t) {
            return e.modules.hasOwnProperty(i.resolve(t))
        }, i
    }, e.register("vue/src/main.js", function (e, t, i) {
        function n(e) {
            var t = this;
            e.data && (e.defaultData = e.data, delete e.data), t !== o && (e = r(e, t.options, !0)), a.processOptions(e);
            var i = function (i, n) {
                    n || (i = r(i, e, !0)), t.call(this, i, !0)
                },
                s = i.prototype = Object.create(t.prototype);
            return a.defProtected(s, "constructor", i), i.extend = n, i.super = t, i.options = e, l.forEach(function (e) {
                i[e] = o[e]
            }), i.use = o.use, i.require = o.require, i
        }

        function r(e, t, i) {
            if (e = e || {}, !t) return e;
            for (var n in t)
                if ("el" !== n) {
                    var s = e[n],
                        c = t[n];
                    i && "function" == typeof s && c ? (e[n] = [s], Array.isArray(c) ? e[n] = e[n].concat(c) : e[n].push(c)) : !i || !a.isTrueObject(s) && !a.isTrueObject(c) || c instanceof o ? void 0 === s && (e[n] = c) : e[n] = r(s, c)
                }
            return e
        }

        var s = t("./config"),
            o = t("./viewmodel"),
            a = t("./utils"),
            c = a.hash,
            l = ["directive", "filter", "partial", "effect", "component"];
        t("./observer"), t("./transition"), o.options = s.globalAssets = {
            directives: t("./directives"),
            filters: t("./filters"),
            partials: c(),
            effects: c(),
            components: c()
        }, l.forEach(function (e) {
            o[e] = function (t, i) {
                var n = this.options[e + "s"];
                return n || (n = this.options[e + "s"] = c()), i ? ("partial" === e ? i = a.toFragment(i) : "component" === e ? i = a.toConstructor(i) : "filter" === e && a.checkFilter(i), n[t] = i, this) : n[t]
            }
        }), o.config = function (e, t) {
            if ("string" == typeof e) {
                if (void 0 === t) return s[e];
                s[e] = t
            } else a.extend(s, e);
            return this
        }, o.use = function (e) {
            if ("string" == typeof e) try {
                e = t(e)
            } catch (i) {
                return
            }
            var n = [].slice.call(arguments, 1);
            return n.unshift(this), "function" == typeof e.install ? e.install.apply(e, n) : e.apply(null, n), this
        }, o.require = function (e) {
            return t("./" + e)
        }, o.extend = n, o.nextTick = a.nextTick, i.exports = o
    }), e.register("vue/src/emitter.js", function (e, t, i) {
        function n(e) {
            this._ctx = e || this
        }

        var r = [].slice,
            s = n.prototype;
        s.on = function (e, t) {
            return this._cbs = this._cbs || {}, (this._cbs[e] = this._cbs[e] || []).push(t), this
        }, s.once = function (e, t) {
            function i() {
                n.off(e, i), t.apply(this, arguments)
            }

            var n = this;
            return this._cbs = this._cbs || {}, i.fn = t, this.on(e, i), this
        }, s.off = function (e, t) {
            if (this._cbs = this._cbs || {}, !arguments.length) return this._cbs = {}, this;
            var i = this._cbs[e];
            if (!i) return this;
            if (1 === arguments.length) return delete this._cbs[e], this;
            for (var n, r = 0; r < i.length; r++)
                if (n = i[r], n === t || n.fn === t) {
                    i.splice(r, 1);
                    break
                }
            return this
        }, s.emit = function (e, t, i, n) {
            this._cbs = this._cbs || {};
            var r = this._cbs[e];
            if (r) {
                r = r.slice(0);
                for (var s = 0, o = r.length; o > s; s++) r[s].call(this._ctx, t, i, n)
            }
            return this
        }, s.applyEmit = function (e) {
            this._cbs = this._cbs || {};
            var t, i = this._cbs[e];
            if (i) {
                i = i.slice(0), t = r.call(arguments, 1);
                for (var n = 0, s = i.length; s > n; n++) i[n].apply(this._ctx, t)
            }
            return this
        }, i.exports = n
    }), e.register("vue/src/config.js", function (e, t, i) {
        var n = t("./text-parser");
        i.exports = {
            prefix: "v",
            debug: !1,
            silent: !1,
            enterClass: "v-enter",
            leaveClass: "v-leave",
            interpolate: !0
        }, Object.defineProperty(i.exports, "delimiters", {
            get: function () {
                return n.delimiters
            },
            set: function (e) {
                n.setDelimiters(e)
            }
        })
    }), e.register("vue/src/utils.js", function (e, t, i) {
        var n, r = t("./config"),
            s = {}.toString,
            o = window,
            a = (o.console, Object.defineProperty),
            c = "object",
            l = /[^\w]this[^\w]/,
            u = "classList" in document.documentElement,
            h = o.requestAnimationFrame || o.webkitRequestAnimationFrame || o.setTimeout,
            f = i.exports = {
                toFragment: t("./fragment"),
                get: function (e, t) {
                    if (t.indexOf(".") < 0) return e[t];
                    for (var i = t.split("."), n = -1, r = i.length; ++n < r && null != e;) e = e[i[n]];
                    return e
                },
                set: function (e, t, i) {
                    if (t.indexOf(".") < 0) return void(e[t] = i);
                    for (var n = t.split("."), r = -1, s = n.length - 1; ++r < s;) null == e[n[r]] && (e[n[r]] = {}), e = e[n[r]];
                    e[n[r]] = i
                },
                baseKey: function (e) {
                    return e.indexOf(".") > 0 ? e.split(".")[0] : e
                },
                hash: function () {
                    return Object.create(null)
                },
                attr: function (e, t) {
                    var i = r.prefix + "-" + t,
                        n = e.getAttribute(i);
                    return null !== n && e.removeAttribute(i), n
                },
                defProtected: function (e, t, i, n, r) {
                    a(e, t, {
                        value: i,
                        enumerable: n,
                        writable: r,
                        configurable: !0
                    })
                },
                isObject: function (e) {
                    return typeof e === c && e && !Array.isArray(e)
                },
                isTrueObject: function (e) {
                    return "[object Object]" === s.call(e)
                },
                bind: function (e, t) {
                    return function (i) {
                        return e.call(t, i)
                    }
                },
                guard: function (e) {
                    return null == e ? "" : "object" == typeof e ? JSON.stringify(e) : e
                },
                checkNumber: function (e) {
                    return isNaN(e) || null === e || "boolean" == typeof e ? e : Number(e)
                },
                extend: function (e, t) {
                    for (var i in t) e[i] !== t[i] && (e[i] = t[i]);
                    return e
                },
                unique: function (e) {
                    for (var t, i = f.hash(), n = e.length, r = []; n--;) t = e[n], i[t] || (i[t] = 1, r.push(t));
                    return r
                },
                toConstructor: function (e) {
                    return n = n || t("./viewmodel"), f.isObject(e) ? n.extend(e) : "function" == typeof e ? e : null
                },
                checkFilter: function (e) {
                    l.test(e.toString()) && (e.computed = !0)
                },
                processOptions: function (e) {
                    var t, i = e.components,
                        n = e.partials,
                        r = e.template,
                        s = e.filters;
                    if (i)
                        for (t in i) i[t] = f.toConstructor(i[t]);
                    if (n)
                        for (t in n) n[t] = f.toFragment(n[t]);
                    if (s)
                        for (t in s) f.checkFilter(s[t]);
                    r && (e.template = f.toFragment(r))
                },
                nextTick: function (e) {
                    h(e, 0)
                },
                addClass: function (e, t) {
                    if (u) e.classList.add(t);
                    else {
                        var i = " " + e.className + " ";
                        i.indexOf(" " + t + " ") < 0 && (e.className = (i + t).trim())
                    }
                },
                removeClass: function (e, t) {
                    if (u) e.classList.remove(t);
                    else {
                        for (var i = " " + e.className + " ", n = " " + t + " "; i.indexOf(n) >= 0;) i = i.replace(n, " ");
                        e.className = i.trim()
                    }
                },
                objectToArray: function (e) {
                    var t, i, n = [];
                    for (var r in e) t = e[r], i = f.isObject(t) ? t : {
                        $value: t
                    }, i.$key = r, n.push(i);
                    return n
                }
            }
    }), e.register("vue/src/fragment.js", function (e, t, i) {
        var n = {
            legend: [1, "<fieldset>", "</fieldset>"],
            tr: [2, "<table><tbody>", "</tbody></table>"],
            col: [2, "<table><tbody></tbody><colgroup>", "</colgroup></table>"],
            _default: [0, "", ""]
        };
        n.td = n.th = [3, "<table><tbody><tr>", "</tr></tbody></table>"], n.option = n.optgroup = [1, '<select multiple="multiple">', "</select>"], n.thead = n.tbody = n.colgroup = n.caption = n.tfoot = [1, "<table>", "</table>"], n.text = n.circle = n.ellipse = n.line = n.path = n.polygon = n.polyline = n.rect = [1, '<svg xmlns="http://www.w3.org/2000/svg" version="1.1">', "</svg>"];
        var r = /<([\w:]+)/;
        i.exports = function (e) {
            if ("string" != typeof e) return e;
            if ("#" === e.charAt(0)) {
                var t = document.getElementById(e.slice(1));
                if (!t) return;
                if ("TEMPLATE" === t.tagName && t.content) return t.content;
                e = t.innerHTML
            }
            var i = document.createDocumentFragment(),
                s = r.exec(e);
            if (!s) return i.appendChild(document.createTextNode(e)), i;
            var o = s[1],
                a = n[o] || n._default,
                c = a[0],
                l = a[1],
                u = a[2],
                h = document.createElement("div");
            for (h.innerHTML = l + e.trim() + u; c--;) h = h.lastChild;
            if (h.firstChild === h.lastChild) return i.appendChild(h.firstChild), i;
            for (var f; f = h.firstChild;) 1 === h.nodeType && i.appendChild(f);
            return i
        }
    }), e.register("vue/src/compiler.js", function (e, t, i) {
        function n(e, t) {
            var i, n, s = this;
            s.init = !0, s.destroyed = !1, t = s.options = t || {}, l.processOptions(t), m(s, t.compilerOptions), s.repeat = s.repeat || !1, s.expCache = s.expCache || {};
            var a = s.el = s.setupElement(t);
            if (s.vm = a.vue_vm = e, s.bindings = l.hash(), s.dirs = [], s.deferred = [], s.computed = [], s.children = [], s.emitter = new o(e), t.methods)
                for (i in t.methods) s.createBinding(i);
            if (t.computed)
                for (i in t.computed) s.createBinding(i);
            e.$ = {}, e.$el = a, e.$options = t, e.$compiler = s, e.$event = null;
            var c = t.parent;
            c && (s.parent = c.$compiler, c.$compiler.children.push(s), e.$parent = c), e.$root = r(s).vm, s.setupObserver();
            var u = s.data = t.data || {},
                h = t.defaultData;
            if (h)
                for (i in h) g.call(u, i) || (u[i] = h[i]);
            var f = t.paramAttributes;
            if (f)
                for (n = f.length; n--;) u[f[n]] = l.checkNumber(s.eval(a.getAttribute(f[n])));
            m(e, u), e.$data = u, s.execHook("created"), u = s.data = e.$data;
            var p;
            for (i in e) p = e[i], "$" !== i.charAt(0) && u[i] !== p && "function" != typeof p && (u[i] = p);
            for (s.observeData(u), t.template && this.resolveContent(), s.compile(a, !0), n = s.deferred.length; n--;) s.bindDirective(s.deferred[n]);
            s.deferred = null, this.computed.length && d.parse(this.computed), s.init = !1, s.execHook("ready")
        }

        function r(e) {
            for (; e.parent;) e = e.parent;
            return e
        }

        var s, o = t("./emitter"),
            a = t("./observer"),
            c = t("./config"),
            l = t("./utils"),
            u = t("./binding"),
            h = t("./directive"),
            f = t("./text-parser"),
            d = t("./deps-parser"),
            p = t("./exp-parser"),
            v = [].slice,
            m = l.extend,
            g = {}.hasOwnProperty,
            b = Object.defineProperty,
            y = ["created", "ready", "beforeDestroy", "afterDestroy", "attached", "detached"],
            _ = ["if", "repeat", "view", "component"],
            x = n.prototype;
        x.setupElement = function (e) {
            var t, i, n, r, s, o = "string" == typeof e.el ? document.querySelector(e.el) : e.el || document.createElement(e.tagName || "div"),
                a = e.template;
            if (a) {
                if (o.hasChildNodes())
                    for (this.rawContent = document.createElement("div"); t = o.firstChild;) this.rawContent.appendChild(t);
                if (e.replace && a.firstChild === a.lastChild) {
                    if (i = a.firstChild.cloneNode(!0), o.parentNode && (o.parentNode.insertBefore(i, o), o.parentNode.removeChild(o)), o.hasAttributes())
                        for (n = o.attributes.length; n--;) r = o.attributes[n], i.setAttribute(r.name, r.value);
                    o = i
                } else o.appendChild(a.cloneNode(!0))
            }
            if (e.id && (o.id = e.id), e.className && (o.className = e.className), s = e.attributes)
                for (r in s) o.setAttribute(r, s[r]);
            return o
        }, x.resolveContent = function () {
            function e(e, t) {
                for (var i = e.parentNode, n = 0, r = t.length; r > n; n++) i.insertBefore(t[n], e);
                i.removeChild(e)
            }

            var t, i, n, r, s, o = v.call(this.el.getElementsByTagName("content")),
                a = this.rawContent;
            if (n = o.length) {
                for (; n--;) t = o[n], a ? (i = t.getAttribute("select"), i ? t.content = v.call(a.querySelectorAll(i)) : s = t) : t.content = v.call(t.childNodes);
                for (n = 0, r = o.length; r > n; n++) t = o[n], t !== s && e(t, t.content);
                a && s && e(s, v.call(a.childNodes))
            }
            this.rawContent = null
        }, x.setupObserver = function () {
            function e(e) {
                r(e), d.catcher.emit("get", a[e])
            }

            function t(e, t, i) {
                l.emit("change:" + e, t, i), r(e), a[e].update(t)
            }

            function i(e, t) {
                l.on("hook:" + e, function () {
                    t.call(s.vm)
                })
            }

            function n(e) {
                var t = s.children;
                if (t)
                    for (var i, n = t.length; n--;) i = t[n], i.el.parentNode && (e = "hook:" + (e ? "attached" : "detached"), i.observer.emit(e), i.emitter.emit(e))
            }

            function r(e) {
                a[e] || s.createBinding(e)
            }

            var s = this,
                a = s.bindings,
                c = s.options,
                l = s.observer = new o(s.vm);
            l.proxies = {}, l.on("get", e).on("set", t).on("mutate", t);
            for (var u, h, f, p = y.length; p--;)
                if (h = y[p], f = c[h], Array.isArray(f))
                    for (u = f.length; u--;) i(h, f[u]);
                else f && i(h, f);
            l.on("hook:attached", function () {
                n(1)
            }).on("hook:detached", function () {
                n(0)
            })
        }, x.observeData = function (e) {
            function t(e) {
                "$data" !== e && i()
            }

            function i() {
                s.update(n.data), r.emit("change:$data", n.data)
            }

            var n = this,
                r = n.observer;
            a.observe(e, "", r);
            var s = n.bindings.$data = new u(n, "$data");
            s.update(e), b(n.vm, "$data", {
                get: function () {
                    return n.observer.emit("get", "$data"), n.data
                },
                set: function (e) {
                    var t = n.data;
                    a.unobserve(t, "", r), n.data = e, a.copyPaths(e, t), a.observe(e, "", r), i()
                }
            }), r.on("set", t).on("mutate", t)
        }, x.compile = function (e, t) {
            var i = e.nodeType;
            1 === i && "SCRIPT" !== e.tagName ? this.compileElement(e, t) : 3 === i && c.interpolate && this.compileTextNode(e)
        }, x.checkPriorityDir = function (e, t, i) {
            var n, r, s;
            if ("component" === e && i !== !0 && (s = this.resolveComponent(t, void 0, !0)) ? (r = this.parseDirective(e, "", t), r.Ctor = s) : (n = l.attr(t, e), r = n && this.parseDirective(e, n, t)), r) {
                if (i === !0) return;
                return this.deferred.push(r), !0
            }
        }, x.compileElement = function (e, t) {
            if ("TEXTAREA" === e.tagName && e.value && (e.value = this.eval(e.value)), e.hasAttributes() || e.tagName.indexOf("-") > -1) {
                if (null !== l.attr(e, "pre")) return;
                var i, n, r, s;
                for (i = 0, n = _.length; n > i; i++)
                    if (this.checkPriorityDir(_[i], e, t)) return;
                e.vue_trans = l.attr(e, "transition"), e.vue_anim = l.attr(e, "animation"), e.vue_effect = this.eval(l.attr(e, "effect"));
                var o, a, u, h, d, p, m = c.prefix + "-",
                    g = v.call(e.attributes),
                    b = this.options.paramAttributes;
                for (i = 0, n = g.length; n > i; i++) {
                    if (o = g[i], a = !1, 0 === o.name.indexOf(m))
                        for (a = !0, p = o.name.slice(m.length), h = this.parseDirective(p, o.value, e, !0), r = 0, s = h.length; s > r; r++) d = h[r], "with" === p ? this.bindDirective(d, this.parent) : this.bindDirective(d);
                    else c.interpolate && (u = f.parseAttr(o.value), u && (d = this.parseDirective("attr", o.name + ":" + u, e), b && b.indexOf(o.name) > -1 ? this.bindDirective(d, this.parent) : this.bindDirective(d)));
                    a && "cloak" !== p && e.removeAttribute(o.name)
                }
            }
            e.hasChildNodes() && v.call(e.childNodes).forEach(this.compile, this)
        }, x.compileTextNode = function (e) {
            var t = f.parse(e.nodeValue);
            if (t) {
                for (var i, n, r, s = 0,
                         o = t.length; o > s; s++) n = t[s], r = null, n.key ? ">" === n.key.charAt(0) ? (i = document.createComment("ref"), r = this.parseDirective("partial", n.key.slice(1), i)) : n.html ? (i = document.createComment(c.prefix + "-html"), r = this.parseDirective("html", n.key, i)) : (i = document.createTextNode(""), r = this.parseDirective("text", n.key, i)) : i = document.createTextNode(n), e.parentNode.insertBefore(i, e), this.bindDirective(r);
                e.parentNode.removeChild(e)
            }
        }, x.parseDirective = function (e, t, i, n) {
            function r(t) {
                return new h(e, t, o, s, i)
            }

            var s = this,
                o = s.getOption("directives", e);
            if (o) {
                var a = h.parse(t);
                return n ? a.map(r) : r(a[0])
            }
        }, x.bindDirective = function (e, t) {
            if (e) {
                if (this.dirs.push(e), e.isEmpty || e.isLiteral) return void(e.bind && e.bind());
                var i, n = t || this,
                    r = e.key;
                if (e.isExp) i = n.createBinding(r, e);
                else {
                    for (; n && !n.hasKey(r);) n = n.parent;
                    n = n || this, i = n.bindings[r] || n.createBinding(r)
                }
                i.dirs.push(e), e.binding = i;
                var s = i.val();
                e.bind && e.bind(s), e.$update(s, !0)
            }
        }, x.createBinding = function (e, t) {
            var i = this,
                n = i.options.methods,
                r = t && t.isExp,
                s = t && t.isFn || n && n[e],
                o = i.bindings,
                c = i.options.computed,
                h = new u(i, e, r, s);
            if (r) i.defineExp(e, h, t);
            else if (s) o[e] = h, h.value = i.vm[e] = n[e];
            else if (o[e] = h, h.root) c && c[e] ? i.defineComputed(e, h, c[e]) : "$" !== e.charAt(0) ? i.defineProp(e, h) : i.defineMeta(e, h);
            else if (c && c[l.baseKey(e)]) i.defineExp(e, h);
            else {
                a.ensurePath(i.data, e);
                var f = e.slice(0, e.lastIndexOf("."));
                o[f] || i.createBinding(f)
            }
            return h
        }, x.defineProp = function (e, t) {
            var i = this,
                n = i.data,
                r = n.__emitter__;
            g.call(n, e) || (n[e] = void 0), r && !g.call(r.values, e) && a.convertKey(n, e), t.value = n[e], b(i.vm, e, {
                get: function () {
                    return i.data[e]
                },
                set: function (t) {
                    i.data[e] = t
                }
            })
        }, x.defineMeta = function (e, t) {
            var i = this.observer;
            t.value = this.data[e], delete this.data[e], b(this.vm, e, {
                get: function () {
                    return a.shouldGet && i.emit("get", e), t.value
                },
                set: function (t) {
                    i.emit("set", e, t)
                }
            })
        }, x.defineExp = function (e, t, i) {
            var n = i && i.computedKey,
                r = n ? i.expression : e,
                s = this.expCache[r];
            s || (s = this.expCache[r] = p.parse(n || e, this)), s && this.markComputed(t, s)
        }, x.defineComputed = function (e, t, i) {
            this.markComputed(t, i), b(this.vm, e, {
                get: t.value.$get,
                set: t.value.$set
            })
        }, x.markComputed = function (e, t) {
            e.isComputed = !0, e.isFn ? e.value = t : ("function" == typeof t && (t = {
                $get: t
            }), e.value = {
                $get: l.bind(t.$get, this.vm),
                $set: t.$set ? l.bind(t.$set, this.vm) : void 0
            }), this.computed.push(e)
        }, x.getOption = function (e, t, i) {
            var n = this.options,
                r = this.parent,
                s = c.globalAssets,
                o = n[e] && n[e][t] || (r ? r.getOption(e, t, i) : s[e] && s[e][t]);
            return o
        }, x.execHook = function (e) {
            e = "hook:" + e, this.observer.emit(e), this.emitter.emit(e)
        }, x.hasKey = function (e) {
            var t = l.baseKey(e);
            return g.call(this.data, t) || g.call(this.vm, t)
        }, x.eval = function (e, t) {
            var i = f.parseAttr(e);
            return i ? p.eval(i, this, t) : e
        }, x.resolveComponent = function (e, i, n) {
            s = s || t("./viewmodel");
            var r = l.attr(e, "component"),
                o = e.tagName,
                a = this.eval(r, i),
                c = o.indexOf("-") > 0 && o.toLowerCase(),
                u = this.getOption("components", a || c, !0);
            return n ? "" === r ? s : u : u || s
        }, x.destroy = function () {
            if (!this.destroyed) {
                var e, t, i, n, r, s, o = this,
                    c = o.vm,
                    l = o.el,
                    u = o.dirs,
                    h = o.computed,
                    f = o.bindings,
                    d = o.children,
                    p = o.parent;
                for (o.execHook("beforeDestroy"), a.unobserve(o.data, "", o.observer), e = u.length; e--;) n = u[e], n.binding && n.binding.compiler !== o && (r = n.binding.dirs, r && (t = r.indexOf(n), t > -1 && r.splice(t, 1))), n.$unbind();
                for (e = h.length; e--;) h[e].unbind();
                for (i in f) s = f[i], s && s.unbind();
                for (e = d.length; e--;) d[e].destroy();
                p && (t = p.children.indexOf(o), t > -1 && p.children.splice(t, 1)), l === document.body ? l.innerHTML = "" : c.$remove(), l.vue_vm = null, o.destroyed = !0, o.execHook("afterDestroy"), o.observer.off(), o.emitter.off()
            }
        }, i.exports = n
    }), e.register("vue/src/viewmodel.js", function (e, t, i) {
        function n(e) {
            new s(this, e)
        }

        function r(e) {
            return "string" == typeof e ? document.querySelector(e) : e
        }

        var s = t("./compiler"),
            o = t("./utils"),
            a = t("./transition"),
            c = t("./batcher"),
            l = [].slice,
            u = o.defProtected,
            h = o.nextTick,
            f = new c,
            d = 1,
            p = n.prototype;
        u(p, "$get", function (e) {
            var t = o.get(this, e);
            return void 0 === t && this.$parent ? this.$parent.$get(e) : t
        }), u(p, "$set", function (e, t) {
            o.set(this, e, t)
        }), u(p, "$watch", function (e, t) {
            function i() {
                var e = l.call(arguments);
                f.push({
                    id: n,
                    override: !0,
                    execute: function () {
                        t.apply(r, e)
                    }
                })
            }

            var n = d++,
                r = this;
            t._fn = i, r.$compiler.observer.on("change:" + e, i)
        }), u(p, "$unwatch", function (e, t) {
            var i = ["change:" + e],
                n = this.$compiler.observer;
            t && i.push(t._fn), n.off.apply(n, i)
        }), u(p, "$destroy", function () {
            this.$compiler.destroy()
        }), u(p, "$broadcast", function () {
            for (var e, t = this.$compiler.children, i = t.length; i--;) e = t[i], e.emitter.applyEmit.apply(e.emitter, arguments), e.vm.$broadcast.apply(e.vm, arguments)
        }), u(p, "$dispatch", function () {
            var e = this.$compiler,
                t = e.emitter,
                i = e.parent;
            t.applyEmit.apply(t, arguments), i && i.vm.$dispatch.apply(i.vm, arguments)
        }), ["emit", "on", "off", "once"].forEach(function (e) {
            var t = "emit" === e ? "applyEmit" : e;
            u(p, "$" + e, function () {
                var e = this.$compiler.emitter;
                e[t].apply(e, arguments)
            })
        }), u(p, "$appendTo", function (e, t) {
            e = r(e);
            var i = this.$el;
            a(i, 1, function () {
                e.appendChild(i), t && h(t)
            }, this.$compiler)
        }), u(p, "$remove", function (e) {
            var t = this.$el;
            a(t, -1, function () {
                t.parentNode && t.parentNode.removeChild(t), e && h(e)
            }, this.$compiler)
        }), u(p, "$before", function (e, t) {
            e = r(e);
            var i = this.$el;
            a(i, 1, function () {
                e.parentNode.insertBefore(i, e), t && h(t)
            }, this.$compiler)
        }), u(p, "$after", function (e, t) {
            e = r(e);
            var i = this.$el;
            a(i, 1, function () {
                e.nextSibling ? e.parentNode.insertBefore(i, e.nextSibling) : e.parentNode.appendChild(i), t && h(t)
            }, this.$compiler)
        }), i.exports = n
    }), e.register("vue/src/binding.js", function (e, t, i) {
        function n(e, t, i, n) {
            this.id = o++, this.value = void 0, this.isExp = !!i, this.isFn = n, this.root = !this.isExp && -1 === t.indexOf("."), this.compiler = e, this.key = t, this.dirs = [], this.subs = [], this.deps = [], this.unbound = !1
        }

        var r = t("./batcher"),
            s = new r,
            o = 1,
            a = n.prototype;
        a.update = function (e) {
            if ((!this.isComputed || this.isFn) && (this.value = e), this.dirs.length || this.subs.length) {
                var t = this;
                s.push({
                    id: this.id,
                    execute: function () {
                        t.unbound || t._update()
                    }
                })
            }
        }, a._update = function () {
            for (var e = this.dirs.length, t = this.val(); e--;) this.dirs[e].$update(t);
            this.pub()
        }, a.val = function () {
            return this.isComputed && !this.isFn ? this.value.$get() : this.value
        }, a.pub = function () {
            for (var e = this.subs.length; e--;) this.subs[e].update()
        }, a.unbind = function () {
            this.unbound = !0;
            for (var e = this.dirs.length; e--;) this.dirs[e].$unbind();
            e = this.deps.length;
            for (var t; e--;) {
                t = this.deps[e].subs;
                var i = t.indexOf(this);
                i > -1 && t.splice(i, 1)
            }
        }, i.exports = n
    }), e.register("vue/src/observer.js", function (e, t, i) {
        function n(e) {
            x(j, e, function () {
                var t, i, n = E.call(arguments),
                    o = Array.prototype[e].apply(this, n);
                return "push" === e || "unshift" === e ? t = n : "pop" === e || "shift" === e ? i = [o] : "splice" === e && (t = n.slice(2), i = o), r(this, t), s(this, i), this.__emitter__.emit("mutate", "", this, {
                    method: e,
                    args: n,
                    result: o,
                    inserted: t,
                    removed: i
                }), o
            }, !A)
        }

        function r(e, t) {
            if (t)
                for (var i, n, r = t.length; r--;) i = t[r], o(i) && (i.__emitter__ || (a(i), l(i)), n = i.__emitter__.owners, n.indexOf(e) < 0 && n.push(e))
        }

        function s(e, t) {
            if (t)
                for (var i, n = t.length; n--;)
                    if (i = t[n], i && i.__emitter__) {
                        var r = i.__emitter__.owners;
                        r && r.splice(r.indexOf(e))
                    }
        }

        function o(e) {
            return "object" == typeof e && e && !e.$compiler
        }

        function a(e) {
            if (e.__emitter__) return !0;
            var t = new y;
            return x(e, "__emitter__", t), t.on("set", function (t, i, n) {
                n && c(e)
            }).on("mutate", function () {
                c(e)
            }), t.values = _.hash(), t.owners = [], !1
        }

        function c(e) {
            for (var t = e.__emitter__.owners, i = t.length; i--;) t[i].__emitter__.emit("set", "", "", !0)
        }

        function l(e) {
            k(e) ? f(e) : h(e)
        }

        function u(e, t) {
            if (A) e.__proto__ = t;
            else
                for (var i in t) x(e, i, t[i])
        }

        function h(e) {
            u(e, O);
            for (var t in e) d(e, t)
        }

        function f(e) {
            u(e, j), r(e, e)
        }

        function d(e, t, i) {
            function n(e, i) {
                o[t] = e, s.emit("set", t, e, i), k(e) && s.emit("set", t + ".length", e.length, i), g(e, t, s)
            }

            var r = t.charAt(0);
            if ("$" !== r && "_" !== r) {
                var s = e.__emitter__,
                    o = s.values;
                n(e[t], i), w(e, t, {
                    enumerable: !0,
                    configurable: !0,
                    get: function () {
                        var e = o[t];
                        return N.shouldGet && s.emit("get", t), e
                    },
                    set: function (e) {
                        var i = o[t];
                        b(i, t, s), v(e, i), n(e, !0)
                    }
                })
            }
        }

        function p(e) {
            var t = e && e.__emitter__;
            if (t)
                if (k(e)) t.emit("set", "length", e.length);
                else {
                    var i, n;
                    for (i in e) n = e[i], t.emit("set", i, n), p(n)
                }
        }

        function v(e, t) {
            if ($(e) && $(t)) {
                var i, n, r;
                for (i in t) C.call(e, i) || (n = t[i], k(n) ? e[i] = [] : $(n) ? (r = e[i] = {}, v(r, n)) : e[i] = void 0)
            }
        }

        function m(e, t) {
            for (var i, n = t.split("."), r = 0, s = n.length - 1; s > r; r++) i = n[r], e[i] || (e[i] = {}, e.__emitter__ && d(e, i)), e = e[i];
            $(e) && (i = n[r], C.call(e, i) || (e[i] = void 0, e.__emitter__ && d(e, i)))
        }

        function g(e, t, i) {
            if (o(e)) {
                var n = t ? t + "." : "",
                    r = a(e),
                    s = e.__emitter__;
                i.proxies = i.proxies || {};
                var c = i.proxies[n] = {
                    get: function (e) {
                        i.emit("get", n + e)
                    },
                    set: function (r, s, o) {
                        r && i.emit("set", n + r, s), t && o && i.emit("set", t, e, !0)
                    },
                    mutate: function (e, r, s) {
                        var o = e ? n + e : t;
                        i.emit("mutate", o, r, s);
                        var a = s.method;
                        "sort" !== a && "reverse" !== a && i.emit("set", o + ".length", r.length)
                    }
                };
                s.on("get", c.get).on("set", c.set).on("mutate", c.mutate), r ? p(e) : l(e)
            }
        }

        function b(e, t, i) {
            if (e && e.__emitter__) {
                t = t ? t + "." : "";
                var n = i.proxies[t];
                n && (e.__emitter__.off("get", n.get).off("set", n.set).off("mutate", n.mutate), i.proxies[t] = null)
            }
        }

        var y = t("./emitter"),
            _ = t("./utils"),
            x = _.defProtected,
            $ = _.isObject,
            k = Array.isArray,
            C = {}.hasOwnProperty,
            w = Object.defineProperty,
            E = [].slice,
            A = {}.__proto__,
            j = Object.create(Array.prototype);
        ["push", "pop", "shift", "unshift", "splice", "sort", "reverse"].forEach(n), x(j, "$set", function (e, t) {
            return this.splice(e, 1, t)[0]
        }, !A), x(j, "$remove", function (e) {
            return "number" != typeof e && (e = this.indexOf(e)), e > -1 ? this.splice(e, 1)[0] : void 0
        }, !A);
        var O = Object.create(Object.prototype);
        x(O, "$add", function (e, t) {
            C.call(this, e) || (this[e] = t, d(this, e, !0))
        }, !A), x(O, "$delete", function (e) {
            C.call(this, e) && (this[e] = void 0, delete this[e], this.__emitter__.emit("delete", e))
        }, !A);
        var N = i.exports = {
            shouldGet: !1,
            observe: g,
            unobserve: b,
            ensurePath: m,
            copyPaths: v,
            watch: l,
            convert: a,
            convertKey: d
        }
    }), e.register("vue/src/directive.js", function (e, t, i) {
        function n(e, t, i, r, o) {
            this.id = s++, this.name = e, this.compiler = r, this.vm = r.vm, this.el = o, this.computeFilters = !1, this.key = t.key, this.arg = t.arg, this.expression = t.expression;
            var a = "" === this.expression;
            if ("function" == typeof i) this[a ? "bind" : "update"] = i;
            else
                for (var u in i) this[u] = i[u];
            if (a || this.isEmpty) return void(this.isEmpty = !0);
            this.expression = (this.isLiteral ? r.eval(this.expression) : this.expression).trim();
            var h, f, d, p, v, m = t.filters;
            if (m)
                for (this.filters = [], d = 0, p = m.length; p > d; d++) h = m[d], f = this.compiler.getOption("filters", h.name), f && (h.apply = f, this.filters.push(h), f.computed && (v = !0));
            this.filters && this.filters.length || (this.filters = null), v && (this.computedKey = n.inlineFilters(this.key, this.filters), this.filters = null), this.isExp = v || !l.test(this.key) || c.test(this.key)
        }

        function r(e) {
            return e.indexOf('"') > -1 ? e.replace(u, "'") : e
        }

        var s = 1,
            o = /^[\w\$-]+$/,
            a = /[^\s'"]+|'[^']+'|"[^"]+"/g,
            c = /^\$(parent|root)\./,
            l = /^[\w\.$]+$/,
            u = /"/g,
            h = n.prototype;
        h.$update = function (e, t) {
            this.$lock || (t || e !== this.value || e && "object" == typeof e) && (this.value = e, this.update && this.update(this.filters && !this.computeFilters ? this.$applyFilters(e) : e, t))
        }, h.$applyFilters = function (e) {
            for (var t, i = e, n = 0, r = this.filters.length; r > n; n++) t = this.filters[n], i = t.apply.apply(this.vm, [i].concat(t.args));
            return i
        }, h.$unbind = function () {
            this.el && this.vm && (this.unbind && this.unbind(), this.vm = this.el = this.binding = this.compiler = null)
        }, n.parse = function (e) {
            function t() {
                v.expression = e.slice(f, g).trim(), void 0 === v.key ? v.key = e.slice(d, g).trim() : m !== f && i(), (0 === g || v.key) && p.push(v)
            }

            function i() {
                var t, i = e.slice(m, g).trim();
                if (i) {
                    t = {};
                    var n = i.match(a);
                    t.name = n[0], t.args = n.length > 1 ? n.slice(1) : null
                }
                t && (v.filters = v.filters || []).push(t), m = g + 1
            }

            for (var n, r, s = !1, c = !1, l = 0, u = 0, h = 0, f = 0, d = 0, p = [], v = {}, m = 0, g = 0,
                     b = e.length; b > g; g++) r = e.charAt(g), s ? "'" === r && (s = !s) : c ? '"' === r && (c = !c) : "," !== r || h || l || u ? ":" !== r || v.key || v.arg ? "|" === r && "|" !== e.charAt(g + 1) && "|" !== e.charAt(g - 1) ? void 0 === v.key ? (m = g + 1, v.key = e.slice(d, g).trim()) : i() : '"' === r ? c = !0 : "'" === r ? s = !0 : "(" === r ? h++ : ")" === r ? h-- : "[" === r ? u++ : "]" === r ? u-- : "{" === r ? l++ : "}" === r && l-- : (n = e.slice(f, g).trim(), o.test(n) && (d = g + 1, v.arg = e.slice(f, g).trim())) : (t(), v = {}, f = d = m = g + 1);
            return (0 === g || f !== g) && t(), p
        }, n.inlineFilters = function (e, t) {
            for (var i, n, s = 0,
                     o = t.length; o > s; s++) n = t[s], i = n.args ? ',"' + n.args.map(r).join('","') + '"' : "", e = 'this.$compiler.getOption("filters", "' + n.name + '").call(this,' + e + i + ")";
            return e
        }, i.exports = n
    }), e.register("vue/src/exp-parser.js", function (e, t) {
        function i(e) {
            return e = e.replace(p, "").replace(v, ",").replace(d, "").replace(m, "").replace(g, ""), e ? e.split(/,+/) : []
        }

        function n(e, t, i) {
            var n = "",
                r = 0,
                s = t;
            if (i && void 0 !== o.get(i, e)) return "$temp.";
            for (; t && !t.hasKey(e);) t = t.parent, r++;
            if (t) {
                for (; r--;) n += "$parent.";
                t.bindings[e] || "$" === e.charAt(0) || t.createBinding(e)
            } else s.createBinding(e);
            return n
        }

        function r(e, t) {
            var i;
            try {
                i = new Function(e)
            } catch (n) {
            }
            return i
        }

        function s(e) {
            return "$" === e.charAt(0) ? "\\" + e : e
        }

        var o = t("./utils"),
            a = /"(?:[^"\\]|\\.)*"|'(?:[^'\\]|\\.)*'/g,
            c = /"(\d+)"/g,
            l = /\n/g,
            u = new RegExp("constructor".split("").join("['\"+, ]*")),
            h = /\\u\d\d\d\d/,
            f = "break,case,catch,continue,debugger,default,delete,do,else,false,finally,for,function,if,in,instanceof,new,null,return,switch,this,throw,true,try,typeof,var,void,while,with,undefined,abstract,boolean,byte,char,class,const,double,enum,export,extends,final,float,goto,implements,import,int,interface,long,native,package,private,protected,public,short,static,super,synchronized,throws,transient,volatile,arguments,let,yield,Math",
            d = new RegExp(["\\b" + f.replace(/,/g, "\\b|\\b") + "\\b"].join("|"), "g"),
            p = /\/\*(?:.|\n)*?\*\/|\/\/[^\n]*\n|\/\/[^\n]*$|'[^']*'|"[^"]*"|[\s\t\n]*\.[\s\t\n]*[$\w\.]+|[\{,]\s*[\w\$_]+\s*:/g,
            v = /[^\w$]+/g,
            m = /\b\d[^,]*/g,
            g = /^,+|,+$/g;
        e.parse = function (e, t, f) {
            function d(e) {
                var t = y.length;
                return y[t] = e.replace(l, "\\n"), '"' + t + '"'
            }

            function p(e) {
                var i = e.charAt(0);
                e = e.slice(1);
                var r = "this." + n(e, t, f) + e;
                return b[e] || (g += r + ";", b[e] = 1), i + r
            }

            function v(e, t) {
                return y[t]
            }

            if (!h.test(e) && !u.test(e)) {
                var m = i(e);
                if (!m.length) return r("return " + e, e);
                m = o.unique(m);
                var g = "",
                    b = o.hash(),
                    y = [],
                    _ = new RegExp("[^$\\w\\.](" + m.map(s).join("|") + ")[$\\w\\.]*\\b", "g"),
                    x = (" " + e).replace(a, d).replace(_, p).replace(c, v);
                return x = g + "return " + x, r(x, e)
            }
        }, e.eval = function (t, i, n) {
            var r, s = e.parse(t, i, n);
            return s && (i.vm.$temp = n, r = s.call(i.vm), delete i.vm.$temp), r
        }
    }), e.register("vue/src/text-parser.js", function (e, t) {
        function i() {
            var e = n(l),
                t = n(u);
            return new RegExp(e + e + e + "?(.+?)" + t + "?" + t + t)
        }

        function n(e) {
            return e.replace(h, "\\$&")
        }

        function r(t) {
            e.delimiters = t, l = t[0], u = t[1], f = i()
        }

        function s(e) {
            if (!f.test(e)) return null;
            for (var t, i, n, r, s = []; t = e.match(f);) i = t.index, i > 0 && s.push(e.slice(0, i)), n = {
                key: t[1].trim()
            }, r = t[0], n.html = r.charAt(2) === l && r.charAt(r.length - 3) === u, s.push(n), e = e.slice(i + t[0].length);
            return e.length && s.push(e), s
        }

        function o(e) {
            c = c || t("./directive");
            var i = s(e);
            if (!i) return null;
            if (1 === i.length) return i[0].key;
            for (var n, r = [], o = 0, l = i.length; l > o; o++) n = i[o], r.push(n.key ? a(n.key) : '"' + n + '"');
            return r.join("+")
        }

        function a(e) {
            if (e.indexOf("|") > -1) {
                var t = c.parse(e),
                    i = t && t[0];
                i && i.filters && (e = c.inlineFilters(i.key, i.filters))
            }
            return "(" + e + ")"
        }

        var c, l = "{",
            u = "}",
            h = /[-.*+?^${}()|[\]\/\\]/g,
            f = i();
        e.parse = s, e.parseAttr = o, e.setDelimiters = r, e.delimiters = [l, u]
    }), e.register("vue/src/deps-parser.js", function (e, t, i) {
        function n(e) {
            if (!e.isFn) {
                var t = o.hash();
                e.deps = [], c.on("get", function (i) {
                    var n = t[i.key];
                    n && n.compiler === i.compiler || i.compiler.repeat && !r(i.compiler, e.compiler) || (t[i.key] = i, e.deps.push(i), i.subs.push(e))
                }), e.value.$get(), c.off("get")
            }
        }

        function r(e, t) {
            for (; t;) {
                if (e === t) return !0;
                t = t.parent
            }
        }

        var s = t("./emitter"),
            o = t("./utils"),
            a = t("./observer"),
            c = new s;
        i.exports = {
            catcher: c,
            parse: function (e) {
                a.shouldGet = !0, e.forEach(n), a.shouldGet = !1
            }
        }
    }), e.register("vue/src/filters.js", function (e, t, i) {
        function n(e, t) {
            if (s.isObject(e)) {
                for (var i in e)
                    if (n(e[i], t)) return !0
            } else if (null != e) return e.toString().toLowerCase().indexOf(t) > -1
        }

        function r(e) {
            return c.test(e) ? e.slice(1, -1) : void 0
        }

        var s = t("./utils"),
            o = s.get,
            a = [].slice,
            c = /^'.*'$/,
            l = i.exports = s.hash();
        l.capitalize = function (e) {
            return e || 0 === e ? (e = e.toString(), e.charAt(0).toUpperCase() + e.slice(1)) : ""
        }, l.uppercase = function (e) {
            return e || 0 === e ? e.toString().toUpperCase() : ""
        }, l.lowercase = function (e) {
            return e || 0 === e ? e.toString().toLowerCase() : ""
        }, l.currency = function (e, t) {
            if (!e && 0 !== e) return "";
            t = t || "$";
            var i = Math.floor(e).toString(),
                n = i.length % 3,
                r = n > 0 ? i.slice(0, n) + (i.length > 3 ? "," : "") : "",
                s = "." + e.toFixed(2).slice(-2);
            return t + r + i.slice(n).replace(/(\d{3})(?=\d)/g, "$1,") + s
        }, l.pluralize = function (e) {
            var t = a.call(arguments, 1);
            return t.length > 1 ? t[e - 1] || t[t.length - 1] : t[e - 1] || t[0] + "s"
        };
        var u = {
            enter: 13,
            tab: 9,
            "delete": 46,
            up: 38,
            left: 37,
            right: 39,
            down: 40,
            esc: 27
        };
        l.key = function (e, t) {
            if (e) {
                var i = u[t];
                return i || (i = parseInt(t, 10)),
                    function (t) {
                        return t.keyCode === i ? e.call(this, t) : void 0
                    }
            }
        }, l.filterBy = function (e, t, i, a) {
            i && "in" !== i && (a = i);
            var c = r(t) || this.$get(t);
            return c ? (c = c.toLowerCase(), a = a && (r(a) || this.$get(a)), Array.isArray(e) || (e = s.objectToArray(e)), e.filter(function (e) {
                return a ? n(o(e, a), c) : n(e, c)
            })) : e
        }, l.filterBy.computed = !0, l.orderBy = function (e, t, i) {
            var n = r(t) || this.$get(t);
            if (!n) return e;
            Array.isArray(e) || (e = s.objectToArray(e));
            var a = 1;
            return i && ("-1" === i ? a = -1 : "!" === i.charAt(0) ? (i = i.slice(1), a = this.$get(i) ? 1 : -1) : a = this.$get(i) ? -1 : 1), e.slice().sort(function (e, t) {
                return e = o(e, n), t = o(t, n), e === t ? 0 : e > t ? a : -a
            })
        }, l.orderBy.computed = !0
    }), e.register("vue/src/transition.js", function (e, t, i) {
        function n(e, t, i, n) {
            if (!o.trans) return i(), f.CSS_SKIP;
            var r, s = e.classList,
                c = e.vue_trans_cb,
                u = a.enterClass,
                h = a.leaveClass,
                d = n ? o.anim : o.trans;
            return c && (e.removeEventListener(d, c), s.remove(u), s.remove(h), e.vue_trans_cb = null), t > 0 ? (s.add(u), i(), n ? (r = function (t) {
                t.target === e && (e.removeEventListener(d, r), e.vue_trans_cb = null, s.remove(u))
            }, e.addEventListener(d, r), e.vue_trans_cb = r) : l.push({
                execute: function () {
                    s.remove(u)
                }
            }), f.CSS_E) : (e.offsetWidth || e.offsetHeight ? (s.add(h), r = function (t) {
                t.target === e && (e.removeEventListener(d, r), e.vue_trans_cb = null, i(), s.remove(h))
            }, e.addEventListener(d, r), e.vue_trans_cb = r) : i(), f.CSS_L)
        }

        function r(e, t, i, n, r) {
            function s(t, i) {
                var n = u(function () {
                    t(), l.splice(l.indexOf(n), 1), l.length || (e.vue_timeouts = null)
                }, i);
                l.push(n)
            }

            var o = r.getOption("effects", n);
            if (!o) return i(), f.JS_SKIP;
            var a = o.enter,
                c = o.leave,
                l = e.vue_timeouts;
            if (l)
                for (var d = l.length; d--;) h(l[d]);
            return l = e.vue_timeouts = [], t > 0 ? "function" != typeof a ? (i(), f.JS_SKIP_E) : (a(e, i, s), f.JS_E) : "function" != typeof c ? (i(), f.JS_SKIP_L) : (c(e, i, s), f.JS_L)
        }

        function s() {
            var e = document.createElement("vue"),
                t = "transitionend",
                i = {
                    transition: t,
                    mozTransition: t,
                    webkitTransition: "webkitTransitionEnd"
                },
                n = {};
            for (var r in i)
                if (void 0 !== e.style[r]) {
                    n.trans = i[r];
                    break
                }
            return n.anim = "" === e.style.animation ? "animationend" : "webkitAnimationEnd", n
        }

        var o = s(),
            a = t("./config"),
            c = t("./batcher"),
            l = new c,
            u = window.setTimeout,
            h = window.clearTimeout,
            f = {
                CSS_E: 1,
                CSS_L: 2,
                JS_E: 3,
                JS_L: 4,
                CSS_SKIP: -1,
                JS_SKIP: -2,
                JS_SKIP_E: -3,
                JS_SKIP_L: -4,
                INIT: -5,
                SKIP: -6
            };
        l._preFlush = function () {
            document.body.offsetHeight
        };
        var d = i.exports = function (e, t, i, s) {
            var o = function () {
                i(), s.execHook(t > 0 ? "attached" : "detached")
            };
            if (s.init) return o(), f.INIT;
            var a = "" === e.vue_trans,
                c = "" === e.vue_anim,
                l = e.vue_effect;
            return l ? r(e, t, o, l, s) : a || c ? n(e, t, o, c) : (o(), f.SKIP)
        };
        d.codes = f
    }), e.register("vue/src/batcher.js", function (e, t, i) {
        function n() {
            this.reset()
        }

        var r = t("./utils"),
            s = n.prototype;
        s.push = function (e) {
            if (e.id && this.has[e.id]) {
                if (e.override) {
                    var t = this.has[e.id];
                    t.cancelled = !0, this.queue.push(e), this.has[e.id] = e
                }
            } else this.queue.push(e), this.has[e.id] = e, this.waiting || (this.waiting = !0, r.nextTick(r.bind(this.flush, this)))
        }, s.flush = function () {
            this._preFlush && this._preFlush();
            for (var e = 0; e < this.queue.length; e++) {
                var t = this.queue[e];
                t.cancelled || t.execute()
            }
            this.reset()
        }, s.reset = function () {
            this.has = r.hash(), this.queue = [], this.waiting = !1
        }, i.exports = n
    }), e.register("vue/src/directives/index.js", function (e, t, i) {
        var n = t("../utils"),
            r = t("../config"),
            s = t("../transition"),
            o = i.exports = n.hash();
        o.component = {
            isLiteral: !0,
            bind: function () {
                this.el.vue_vm || (this.childVM = new this.Ctor({
                    el: this.el,
                    parent: this.vm
                }))
            },
            unbind: function () {
                this.childVM && this.childVM.$destroy()
            }
        }, o.attr = {
            bind: function () {
                var e = this.vm.$options.paramAttributes;
                this.isParam = e && e.indexOf(this.arg) > -1
            },
            update: function (e) {
                e || 0 === e ? this.el.setAttribute(this.arg, e) : this.el.removeAttribute(this.arg), this.isParam && (this.vm[this.arg] = n.checkNumber(e))
            }
        }, o.text = {
            bind: function () {
                this.attr = 3 === this.el.nodeType ? "nodeValue" : "textContent"
            },
            update: function (e) {
                this.el[this.attr] = n.guard(e)
            }
        }, o.show = function (e) {
            var t = this.el,
                i = e ? "" : "none",
                n = function () {
                    t.style.display = i
                };
            s(t, e ? 1 : -1, n, this.compiler)
        }, o["class"] = function (e) {
            this.arg ? n[e ? "addClass" : "removeClass"](this.el, this.arg) : (this.lastVal && n.removeClass(this.el, this.lastVal), e && (n.addClass(this.el, e), this.lastVal = e))
        }, o.cloak = {
            isEmpty: !0,
            bind: function () {
                var e = this.el;
                this.compiler.observer.once("hook:ready", function () {
                    e.removeAttribute(r.prefix + "-cloak")
                })
            }
        }, o.ref = {
            isLiteral: !0,
            bind: function () {
                var e = this.expression;
                e && (this.vm.$parent.$[e] = this.vm)
            },
            unbind: function () {
                var e = this.expression;
                e && delete this.vm.$parent.$[e]
            }
        }, o.on = t("./on"), o.repeat = t("./repeat"), o.model = t("./model"), o["if"] = t("./if"), o["with"] = t("./with"), o.html = t("./html"), o.style = t("./style"), o.partial = t("./partial"), o.view = t("./view")
    }), e.register("vue/src/directives/if.js", function (e, t, i) {
        var n = t("../utils");
        i.exports = {
            bind: function () {
                this.parent = this.el.parentNode, this.ref = document.createComment("vue-if"), this.Ctor = this.compiler.resolveComponent(this.el), this.parent.insertBefore(this.ref, this.el), this.parent.removeChild(this.el), n.attr(this.el, "view"), n.attr(this.el, "repeat")
            },
            update: function (e) {
                e ? this.childVM || (this.childVM = new this.Ctor({
                        el: this.el.cloneNode(!0),
                        parent: this.vm
                    }), this.compiler.init ? this.parent.insertBefore(this.childVM.$el, this.ref) : this.childVM.$before(this.ref)) : this.unbind()
            },
            unbind: function () {
                this.childVM && (this.childVM.$destroy(), this.childVM = null)
            }
        }
    }), e.register("vue/src/directives/repeat.js", function (e, t, i) {
        function n(e, t) {
            for (var i, n = 0, r = e.length; r > n; n++)
                if (i = e[n], !i.$reused && i.$value === t) return n;
            return -1
        }

        var r = t("../utils"),
            s = t("../config");
        i.exports = {
            bind: function () {
                this.identifier = "$r" + this.id, this.expCache = r.hash();
                var e = this.el,
                    t = this.container = e.parentNode;
                this.childId = this.compiler.eval(r.attr(e, "ref")), this.ref = document.createComment(s.prefix + "-repeat-" + this.key), t.insertBefore(this.ref, e), t.removeChild(e), this.collection = null, this.vms = null
            },
            update: function (e) {
                Array.isArray(e) || r.isObject(e) && (e = r.objectToArray(e)), this.oldVMs = this.vms, this.oldCollection = this.collection, e = this.collection = e || [];
                var t = e[0] && r.isObject(e[0]);
                this.vms = this.oldCollection ? this.diff(e, t) : this.init(e, t), this.childId && (this.vm.$[this.childId] = this.vms)
            },
            init: function (e, t) {
                for (var i, n = [], r = 0,
                         s = e.length; s > r; r++) i = this.build(e[r], r, t), n.push(i), this.compiler.init ? this.container.insertBefore(i.$el, this.ref) : i.$before(this.ref);
                return n
            },
            diff: function (e, t) {
                var i, r, s, o, a, c, l, u, h = this.container,
                    f = this.oldVMs,
                    d = [];
                for (d.length = e.length, i = 0, r = e.length; r > i; i++) s = e[i], t ? (s.$index = i, s.__emitter__ && s.__emitter__[this.identifier] ? s.$reused = !0 : d[i] = this.build(s, i, t)) : (a = n(f, s), a > -1 ? (f[a].$reused = !0, f[a].$data.$index = i) : d[i] = this.build(s, i, t));
                for (i = 0, r = f.length; r > i; i++) o = f[i], s = this.arg ? o.$data[this.arg] : o.$data, s.$reused && (o.$reused = !0, delete s.$reused), o.$reused ? (o.$index = s.$index, s.$key && s.$key !== o.$key && (o.$key = s.$key), d[o.$index] = o) : (s.__emitter__ && delete s.__emitter__[this.identifier], o.$destroy());
                for (i = d.length; i--;)
                    if (o = d[i], s = o.$data, c = d[i + 1], o.$reused) {
                        for (u = o.$el.nextSibling; !u.vue_vm && u !== this.ref;) u = u.nextSibling;
                        if (l = u.vue_vm, l !== c)
                            if (c) {
                                for (u = c.$el; !u.parentNode;) c = d[u.vue_vm.$index + 1], u = c ? c.$el : this.ref;
                                h.insertBefore(o.$el, u)
                            } else h.insertBefore(o.$el, this.ref);
                        delete o.$reused, delete s.$index, delete s.$key
                    } else o.$before(c ? c.$el : this.ref);
                return d
            },
            build: function (e, t, i) {
                var n, r, s = !i || this.arg;
                s && (n = e, r = this.arg || "$value", e = {}, e[r] = n), e.$index = t;
                var o = this.el.cloneNode(!0),
                    a = this.compiler.resolveComponent(o, e),
                    c = new a({
                        el: o,
                        data: e,
                        parent: this.vm,
                        compilerOptions: {
                            repeat: !0,
                            expCache: this.expCache
                        }
                    });
                return i && ((n || e).__emitter__[this.identifier] = !0), c
            },
            unbind: function () {
                if (this.childId && delete this.vm.$[this.childId], this.vms)
                    for (var e = this.vms.length; e--;) this.vms[e].$destroy()
            }
        }
    }), e.register("vue/src/directives/on.js", function (e, t, i) {
        t("../utils");
        i.exports = {
            isFn: !0,
            bind: function () {
                this.context = this.binding.isExp ? this.vm : this.binding.compiler.vm
            },
            update: function (e) {
                if ("function" == typeof e) {
                    this.unbind();
                    var t = this.vm,
                        i = this.context;
                    this.handler = function (n) {
                        n.targetVM = t, i.$event = n;
                        var r = e.call(i, n);
                        return i.$event = null, r
                    }, this.el.addEventListener(this.arg, this.handler)
                }
            },
            unbind: function () {
                this.el.removeEventListener(this.arg, this.handler)
            }
        }
    }), e.register("vue/src/directives/model.js", function (e, t, i) {
        function n(e) {
            return o.call(e.options, function (e) {
                return e.selected
            }).map(function (e) {
                return e.value || e.text
            })
        }

        var r = t("../utils"),
            s = navigator.userAgent.indexOf("MSIE 9.0") > 0,
            o = [].filter;
        i.exports = {
            bind: function () {
                var e = this,
                    t = e.el,
                    i = t.type,
                    n = t.tagName;
                e.lock = !1, e.ownerVM = e.binding.compiler.vm, e.event = e.compiler.options.lazy || "SELECT" === n || "checkbox" === i || "radio" === i ? "change" : "input", e.attr = "checkbox" === i ? "checked" : "INPUT" === n || "SELECT" === n || "TEXTAREA" === n ? "value" : "innerHTML", "SELECT" === n && t.hasAttribute("multiple") && (this.multi = !0);
                var o = !1;
                e.cLock = function () {
                    o = !0
                }, e.cUnlock = function () {
                    o = !1
                }, t.addEventListener("compositionstart", this.cLock), t.addEventListener("compositionend", this.cUnlock), e.set = e.filters ? function () {
                    if (!o) {
                        var i;
                        try {
                            i = t.selectionStart
                        } catch (n) {
                        }
                        e._set(), r.nextTick(function () {
                            void 0 !== i && t.setSelectionRange(i, i)
                        })
                    }
                } : function () {
                    o || (e.lock = !0, e._set(), r.nextTick(function () {
                        e.lock = !1
                    }))
                }, t.addEventListener(e.event, e.set), s && (e.onCut = function () {
                    r.nextTick(function () {
                        e.set()
                    })
                }, e.onDel = function (t) {
                    (46 === t.keyCode || 8 === t.keyCode) && e.set()
                }, t.addEventListener("cut", e.onCut), t.addEventListener("keyup", e.onDel))
            },
            _set: function () {
                this.ownerVM.$set(this.key, this.multi ? n(this.el) : this.el[this.attr])
            },
            update: function (e, t) {
                if (t && void 0 === e) return this._set();
                if (!this.lock) {
                    var i = this.el;
                    "SELECT" === i.tagName ? (i.selectedIndex = -1, this.multi && Array.isArray(e) ? e.forEach(this.updateSelect, this) : this.updateSelect(e)) : "radio" === i.type ? i.checked = e == i.value : "checkbox" === i.type ? i.checked = !!e : i[this.attr] = r.guard(e)
                }
            },
            updateSelect: function (e) {
                for (var t = this.el.options, i = t.length; i--;)
                    if (t[i].value == e) {
                        t[i].selected = !0;
                        break
                    }
            },
            unbind: function () {
                var e = this.el;
                e.removeEventListener(this.event, this.set), e.removeEventListener("compositionstart", this.cLock), e.removeEventListener("compositionend", this.cUnlock), s && (e.removeEventListener("cut", this.onCut), e.removeEventListener("keyup", this.onDel))
            }
        }
    }), e.register("vue/src/directives/with.js", function (e, t, i) {
        var n = t("../utils");
        i.exports = {
            bind: function () {
                var e = this,
                    t = e.arg,
                    i = e.key,
                    r = e.compiler,
                    s = e.binding.compiler;
                return r === s ? void(this.alone = !0) : void(t && (r.bindings[t] || r.createBinding(t), r.observer.on("change:" + t, function (t) {
                    r.init || (e.lock || (e.lock = !0, n.nextTick(function () {
                        e.lock = !1
                    })), s.vm.$set(i, t))
                })))
            },
            update: function (e) {
                this.alone || this.lock || (this.arg ? this.vm.$set(this.arg, e) : this.vm.$data = e)
            }
        }
    }), e.register("vue/src/directives/html.js", function (e, t, i) {
        var n = t("../utils"),
            r = [].slice;
        i.exports = {
            bind: function () {
                8 === this.el.nodeType && (this.nodes = [])
            },
            update: function (e) {
                e = n.guard(e), this.nodes ? this.swap(e) : this.el.innerHTML = e
            },
            swap: function (e) {
                for (var t = this.el.parentNode, i = this.nodes, s = i.length; s--;) t.removeChild(i[s]);
                var o = n.toFragment(e);
                this.nodes = r.call(o.childNodes), t.insertBefore(o, this.el)
            }
        }
    }), e.register("vue/src/directives/style.js", function (e, t, i) {
        function n(e) {
            return e[1].toUpperCase()
        }

        var r = /-([a-z])/g,
            s = ["webkit", "moz", "ms"];
        i.exports = {
            bind: function () {
                var e = this.arg;
                if (e) {
                    var t = e.charAt(0);
                    "$" === t ? (e = e.slice(1), this.prefixed = !0) : "-" === t && (e = e.slice(1)), this.prop = e.replace(r, n)
                }
            },
            update: function (e) {
                var t = this.prop;
                if (t) {
                    if (this.el.style[t] = e, this.prefixed) {
                        t = t.charAt(0).toUpperCase() + t.slice(1);
                        for (var i = s.length; i--;) this.el.style[s[i] + t] = e
                    }
                } else this.el.style.cssText = e
            }
        }
    }), e.register("vue/src/directives/partial.js", function (e, t, i) {
        t("../utils");
        i.exports = {
            isLiteral: !0,
            bind: function () {
                var e = this.expression;
                if (e) {
                    var t = this.el,
                        i = this.compiler,
                        n = i.getOption("partials", e);
                    if (n)
                        if (n = n.cloneNode(!0), 8 === t.nodeType) {
                            var r = [].slice.call(n.childNodes),
                                s = t.parentNode;
                            s.insertBefore(n, t), s.removeChild(t), r.forEach(i.compile, i)
                        } else t.innerHTML = "", t.appendChild(n.cloneNode(!0))
                }
            }
        }
    }), e.register("vue/src/directives/view.js", function (e, t, i) {
        i.exports = {
            bind: function () {
                var e = this.raw = this.el,
                    t = e.parentNode,
                    i = this.ref = document.createComment("v-view");
                t.insertBefore(i, e), t.removeChild(e);
                for (var n, r = this.inner = document.createElement("div"); n = e.firstChild;) r.appendChild(n)
            },
            update: function (e) {
                this.unbind();
                var t = this.compiler.getOption("components", e);
                t && (this.childVM = new t({
                    el: this.raw.cloneNode(!0),
                    parent: this.vm,
                    compilerOptions: {
                        rawContent: this.inner.cloneNode(!0)
                    }
                }), this.el = this.childVM.$el, this.compiler.init ? this.ref.parentNode.insertBefore(this.el, this.ref) : this.childVM.$before(this.ref))
            },
            unbind: function () {
                this.childVM && this.childVM.$destroy()
            }
        }
    }), e.alias("vue/src/main.js", "vue/index.js"), "object" == typeof exports ? module.exports = e("vue") : "function" == typeof define && define.amd ? define(function () {
        return e("vue")
    }) : window.Vue = e("vue")
}();

function KeyboardInputManager() {
    this.events = {};

    if (window.navigator.msPointerEnabled) {
        //Internet Explorer 10 style
        this.eventTouchstart = "MSPointerDown";
        this.eventTouchmove = "MSPointerMove";
        this.eventTouchend = "MSPointerUp";
    } else {
        this.eventTouchstart = "touchstart";
        this.eventTouchmove = "touchmove";
        this.eventTouchend = "touchend";
    }

    this.listen();
}

KeyboardInputManager.prototype.on = function (event, callback) {
    if (!this.events[event]) {
        this.events[event] = [];
    }
    this.events[event].push(callback);
};

KeyboardInputManager.prototype.emit = function (event, data) {
    var callbacks = this.events[event];
    if (callbacks) {
        callbacks.forEach(function (callback) {
            callback(data);
        });
    }
};

KeyboardInputManager.prototype.listen = function () {
    var self = this;

    var map = {
        38: 0, // Up
        39: 1, // Right
        40: 2, // Down
        37: 3, // Left
        75: 0, // Vim up
        76: 1, // Vim right
        74: 2, // Vim down
        72: 3, // Vim left
        87: 0, // W
        68: 1, // D
        83: 2, // S
        65: 3 // A
    };

    // Respond to direction keys
    document.addEventListener("keydown", function (event) {

        var modifiers = event.altKey || event.ctrlKey || event.metaKey ||
            event.shiftKey;
        var mapped = map[event.which];

        if (!modifiers) {
            if (mapped !== undefined) {
                event.preventDefault();
                self.emit("move", mapped);
            }
        }

        // R key restarts the game
        if (!modifiers && event.which === 82) {
            self.restart.call(self, event);
        }
    });

    // Respond to button presses
    // this.bindButtonPress(".retry-button", this.restart);
    // this.bindButtonPress(".restart-button", this.restart);
    // this.bindButtonPress(".keep-playing-button", this.keepPlaying);

    // Respond to swipe events
    var touchStartClientX, touchStartClientY;
    var gameContainer = document.getElementsByClassName("game-container")[0];

    gameContainer.addEventListener(this.eventTouchstart, function (event) {
        if ((!window.navigator.msPointerEnabled && event.touches.length > 1) ||
            event.targetTouches > 1) {
            return; // Ignore if touching with more than 1 finger
        }

        if (window.navigator.msPointerEnabled) {
            touchStartClientX = event.pageX;
            touchStartClientY = event.pageY;
        } else {
            touchStartClientX = event.touches[0].clientX;
            touchStartClientY = event.touches[0].clientY;
        }

        event.preventDefault();
    });

    gameContainer.addEventListener(this.eventTouchmove, function (event) {
        event.preventDefault();
    });

    gameContainer.addEventListener(this.eventTouchend, function (event) {
        if ((!window.navigator.msPointerEnabled && event.touches.length > 0) ||
            event.targetTouches > 0) {
            return; // Ignore if still touching with one or more fingers
        }

        var touchEndClientX, touchEndClientY;

        if (window.navigator.msPointerEnabled) {
            touchEndClientX = event.pageX;
            touchEndClientY = event.pageY;
        } else {
            touchEndClientX = event.changedTouches[0].clientX;
            touchEndClientY = event.changedTouches[0].clientY;
        }

        var dx = touchEndClientX - touchStartClientX;
        var absDx = Math.abs(dx);

        var dy = touchEndClientY - touchStartClientY;
        var absDy = Math.abs(dy);

        if (Math.max(absDx, absDy) > 10) {
            // (right : left) : (down : up)
            self.emit("move", absDx > absDy ? (dx > 0 ? 1 : 3) : (dy > 0 ? 2 : 0));
        }
    });
};
(function (exports) {

    'use strict';

    exports.gameStorage = {
        fetch: function (STORAGE_KEY) {
            return JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
        },
        save: function (STORAGE_KEY, data) {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
        }
    };

})(window);
(function (win) {

    var Game = new Vue({
        el: '#mainVue',
        data: {
            tileDimension: 124,
            tilePosition: 121,
            startTiles: 2,
            tiles: [],
            grid: [],
            conf: gameStorage.fetch('vue2048-config')
        },

        created: function () {
            this.getWindowSize();
        },

        ready: function () {
            var data = gameStorage.fetch('vue2048'),
                conf = this.conf;


            if (conf.score) {
                this.continueGame(data);
            } else {

                if (conf.length === 0) {
                    //First Kick
                    this.conf = {
                        score: 0,
                        size: 4,
                        bestScore: 0
                    };
                }

                this.init();
            }

            this.$watch('tiles', function (tiles) {
                gameStorage.save('vue2048', tiles);
            });

            this.$watch('conf', function (conf) {
                gameStorage.save('vue2048-config', conf);
            });

        },
        //Can go to templates
        computed: {
            findDimension: function () {
                return this.grid.length * this.tileDimension;
            },

            selected: function () {
                return '';
            },

            allDone: {
                $get: function () {
                    return this.remaining === 0;
                },
                $set: function (value) {
                    this.todos.forEach(function (todo) {
                        todo.completed = value;
                    });
                }
            }
        },

        components: {

            tile: {

                replace: true,

                computed: {
                    calcStyleX: function (cord) {
                        var tilePosition = this.$parent.tilePosition;

                        return tilePosition * this.x;
                    },

                    calcStyleY: function (cord) {
                        var tilePosition = this.$parent.tilePosition;

                        return tilePosition * this.y;
                    },
                    isMerged: function () {
                        return 'tile-merged';
                    }
                }
            }
        },

        directives: {
            selected: {
                isEmpty: true,
                bind: function () {
                    if (this.vm.conf.size)
                        this.el.value = this.vm.conf.size;
                }
            }
        },


        methods: {

            init: function () {

                var startTiles = this.startTiles;

                this.initArrayGrid(this.conf.size);
                this.clearMessage();

                this.tiles = [];
                this.updateScore(0);

                for (var i = 0; i < startTiles; i++) {
                    this.addRandomTile();
                }
            },

            continueGame: function (data) {
                var arr,
                    conf;

                conf = this.conf;
                this.initArrayGrid(conf.size);
                arr = this.grid;
                this.tiles = data;

                data.forEach(function (item) {
                    arr[item.x][item.y] = 1;
                });
            },

            gameOver: function () {
                this.message();
            },

            initArrayGrid: function (size) {
                var arr = [];

                for (var x = 0; x < size; x++) {
                    arr[x] = [];
                    for (var y = 0; y < size; y++) {
                        arr[x][y] = 0;
                    }
                }

                this.grid = arr;
            },

            changesTilesSize: function (e) {
                e.preventDefault();
                this.conf.size = parseInt(e.target.value);

                if (document.activeElement)
                    document.activeElement.blur();

                this.init();
            },

            addRandomTile: function () {

                if (this.availableCells().length > 0) {
                    var value = Math.random() < 0.9 ? 2 : 4,
                        randomCell = this.randomAvailableCell();

                    this.addTile({
                        x: randomCell.x,
                        y: randomCell.y,
                        value: value,
                        merged: false
                    });
                }

            },

            addTile: function (tile) {

                var tiles = this.tiles,
                    len = tiles.length;

                tiles.$set(len, {
                    x: tile.x,
                    y: tile.y,
                    value: tile.value,
                    merged: tile.merged,
                });

                this.grid[tile.x][tile.y] = 1;
            },

            // Find the first available random position
            randomAvailableCell: function () {
                var cells = this.availableCells();

                if (cells.length) {
                    return cells[Math.floor(Math.random() * cells.length)];
                }
            },

            availableCells: function () {
                var cells = [],
                    size = this.conf.size,
                    grid = this.grid;

                for (var x = 0; x < size; x++) {
                    for (var y = 0; y < size; y++) {
                        if (!grid[x][y]) {
                            cells.push({
                                x: x,
                                y: y
                            });
                        }
                    }
                }

                return cells;
            },

            getVector: function (direction) {
                var map = {
                    0: {
                        x: 0,
                        y: -1
                    }, // Up
                    1: {
                        x: 1,
                        y: 0
                    }, // Right
                    2: {
                        x: 0,
                        y: 1
                    }, // Down
                    3: {
                        x: -1,
                        y: 0
                    } // Left
                };

                return map[direction];
            },

            findFarthestPosition: function (cell, vector) {
                var previous;

                do {
                    previous = cell;
                    cell = {
                        x: previous.x + vector.x,
                        y: previous.y + vector.y
                    };

                } while (this.withinBounds(cell) && !this.grid[cell.x][cell.y]);

                return {
                    farthest: previous,
                    next: cell // Used to check if a merge is required
                };
            },

            findTile: function (position) {

                if (position.x === -1 || position.y === -1)
                    return null;
                else {
                    var tiles = this.tiles;

                    return tiles.filter(function (item, index) {
                        return item.x === position.x && item.y === position.y;
                    })[0];
                }

            },

            moveTile: function (tile, position) {

                if (tile.x === position.x && tile.y === position.y) {
                    return false;
                } else {
                    this.grid[tile.x][tile.y] = 0;
                    this.grid[position.x][position.y] = 1;

                    tile.x = position.x;
                    tile.y = position.y;

                    return true;
                }

            },

            mergeTiles: function (curr, next, position) {

                next.value *= 2;
                next.merged = true;

                var tiles = this.tiles;

                //Better Way to find index of data
                for (var key in tiles) {
                    if (tiles[key].x === curr.x && tiles[key].y === curr.y) {
                        this.tiles.$remove(parseInt(key));
                        break;
                    }
                }

                this.grid[curr.x][curr.y] = 0;

                // Update the score
                this.updateScore(next.value);

                return true;
            },

            move: function (direction) {

                var vector = this.getVector(direction);
                var traversals = this.buildTraversals(vector);
                var moved = false;
                var self = this;
                var grid = self.grid;
                var positions;
                var next;
                var tile;


                traversals.x.forEach(function (x) {
                    traversals.y.forEach(function (y) {
                        // console.log(x, y);
                        if (grid[x][y]) {
                            var tile = self.findTile({
                                x: x,
                                y: y
                            });

                            //tile.merged = false;

                            var positions = self.findFarthestPosition({
                                x: x,
                                y: y
                            }, vector);
                            //console.log(positions);
                            var next = self.findTile(positions.next);

                            //console.log(next);
                            // Only one merger per row traversal?
                            if (next && next.value === tile.value) {

                                moved = self.mergeTiles(tile, next, positions.next);


                            } else {
                                moved = self.moveTile(tile, positions.farthest);
                            }

                        }

                    });
                });

                if (moved) {
                    this.addRandomTile();

                    if (grid.toString().indexOf('0') === -1) {
                        if (!this.tileMatchesAvailable()) {
                            this.gameOver();
                        }

                    }

                }

            },

            tileMatchesAvailable: function () {

                var size = this.conf.size;
                var grid = this.grid;
                var tiles = this.tiles;
                var tile;

                for (var x = 0; x < size; x++) {
                    for (var y = 0; y < size; y++) {
                        tile = grid[x][y];

                        if (tile) {
                            for (var direction = 0; direction < 4; direction++) {
                                var vector = this.getVector(direction);
                                var cell = {
                                        x: x + vector.x,
                                        y: y + vector.y
                                    },
                                    other;

                                if (cell.x >= 0 && cell.x < size && cell.y >= 0 && cell.y < size) {
                                    other = grid[cell.x][cell.y];
                                } else {
                                    continue;
                                }

                                if (other && this.findTile(cell).value === this.findTile({
                                        x: x,
                                        y: y
                                    }).value) {
                                    return true; // These two tiles can be merged
                                }
                            }
                        }
                    }
                }

                return false;
            },

            withinBounds: function (position) {
                var size = this.conf.size;

                return position.x >= 0 && position.x < size && position.y >= 0 && position.y < size;
            },

            buildTraversals: function (vector) {
                var traversals = {
                        x: [],
                        y: []
                    },
                    size = this.conf.size;

                for (var pos = 0; pos < size; pos++) {
                    traversals.x.push(pos);
                    traversals.y.push(pos);
                }

                // Always traverse from the farthest cell in the chosen direction
                if (vector.x === 1) traversals.x = traversals.x.reverse();
                if (vector.y === 1) traversals.y = traversals.y.reverse();

                return traversals;
            },

            updateScore: function (score) {
                var scoreContainer = document.getElementsByClassName('score-container')[0];

                //On init
                if (score === 0) {
                    this.conf.score = 0;
                    //gameStorage.save('score', 0);

                    return false;
                }


                this.conf.score += score;
                //gameStorage.save('score', this.score);

                if (this.conf.score > this.conf.bestScore) {
                    this.conf.bestScore = this.conf.score;
                    //gameStorage.save('bestScore', this.bestScore);
                }

                // The mighty 2048 tile
                if (score === 2048)
                    this.message(true);

                var addition = document.createElement("div");
                addition.classList.add("score-addition");
                addition.textContent = "+" + score;
                scoreContainer.appendChild(addition);

            },

            message: function (won) {
                var type = won ? "game-won" : "game-over";
                var message = won ? "你赢了" : "你输了";
                var messageContainer = document.querySelector(".game-message");

                messageContainer.classList.add(type);
                messageContainer.getElementsByTagName("p")[0].textContent = message;
            },

            clearMessage: function () {
                messageContainer = document.querySelector(".game-message");

                messageContainer.classList.remove("game-won");
                messageContainer.classList.remove("game-over");
            },

            clearContainer: function (container) {
                while (container.firstChild) {
                    container.removeChild(container.firstChild);
                }
            },

            getWindowSize: function () {
                var w = window,
                    d = document,
                    e = d.documentElement,
                    g = d.getElementsByTagName('body')[0],
                    x = w.innerWidth || e.clientWidth || g.clientWidth,
                    y = w.innerHeight || e.clientHeight || g.clientHeight;


                if (x < 520) {
                    this.tileDimension = 69.5;
                    this.tilePosition = 67;
                } else {
                    this.tileDimension = 124;
                    this.tilePosition = 121;

                }
            }

        }
    });


    var Keys = new KeyboardInputManager();


    Keys.on('move', function (direction) {
        Game.move(direction);
    });


    win.onresize = function (event) {
        Game.getWindowSize();
    };

})(window);