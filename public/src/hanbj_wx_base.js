var wx_init = (function ($, w, undefined) {
    'use strict';
    var loading;
    var pageManager = {
        $container: $('#container'),
        _pageStack: [],
        _configs: [],
        _pageAppend: function () {
        },
        _defaultPage: null,
        _pageIndex: 1,
        setDefault: function (defaultPage) {
            this._defaultPage = this._find('name', defaultPage);
            return this;
        },
        setPageAppend: function (pageAppend) {
            this._pageAppend = pageAppend;
            return this;
        },
        init: function () {
            var self = this;

            $(w).on('hashchange', function () {
                var state = history.state || {};
                var url = location.hash.indexOf('#') === 0 ? location.hash : '#';
                var page = self._find('url', url) || self._defaultPage;
                if (state._pageIndex <= self._pageIndex || self._findInStack(url)) {
                    self._back(page);
                } else {
                    self._go(page);
                }
            });

            if (history.state && history.state._pageIndex) {
                this._pageIndex = history.state._pageIndex;
            }

            this._pageIndex--;

            var url = location.hash.indexOf('#') === 0 ? location.hash : '#';
            var page = self._find('url', url) || self._defaultPage;
            this._go(page);
            return this;
        },
        push: function (config) {
            this._configs.push(config);
            return this;
        },
        go: function (to) {
            var config = this._find('name', to);
            if (!config) {
                return;
            }
            location.hash = config.url;
        },
        _go: function (config) {
            this._pageIndex++;

            history.replaceState && history.replaceState({_pageIndex: this._pageIndex}, '', location.href);

            var html = $(config.template).html();
            var $html = $(html).addClass('slideIn').addClass(config.name);
            $html.on('animationend webkitAnimationEnd', function () {
                $html.removeClass('slideIn').addClass('js_show');
            });
            this.$container.append($html);
            this._pageAppend.call(this, $html);
            this._pageStack.push({
                config: config,
                dom: $html
            });

            if (!config.isBind) {
                this._bind(config);
            }

            return this;
        },
        back: function () {
            history.back();
        },
        _back: function (config) {
            this._pageIndex--;

            var stack = this._pageStack.pop();
            if (!stack) {
                return;
            }

            var url = location.hash.indexOf('#') === 0 ? location.hash : '#';
            var found = this._findInStack(url);
            if (!found) {
                var html = $(config.template).html();
                var $html = $(html).addClass('js_show').addClass(config.name);
                $html.insertBefore(stack.dom);

                if (!config.isBind) {
                    this._bind(config);
                }

                this._pageStack.push({
                    config: config,
                    dom: $html
                });
            }

            stack.dom.addClass('slideOut').on('animationend webkitAnimationEnd', function () {
                stack.dom.remove();
            });

            return this;
        },
        _findInStack: function (url) {
            var found = null;
            for (var i = 0, len = this._pageStack.length; i < len; i++) {
                var stack = this._pageStack[i];
                if (stack.config.url === url) {
                    found = stack;
                    break;
                }
            }
            return found;
        },
        _find: function (key, value) {
            var page = null;
            for (var i = 0, len = this._configs.length; i < len; i++) {
                if (this._configs[i][key] === value) {
                    page = this._configs[i];
                    break;
                }
            }
            return page;
        },
        _bind: function (page) {
            var events = page.events || {};
            for (var t in events) {
                for (var type in events[t]) {
                    this.$container.on(type, t, events[t][type]);
                }
            }
            page.isBind = true;
        }
    };

    var fastClick = function () {
        var supportTouch = function () {
            try {
                document.createEvent("TouchEvent");
                return true;
            } catch (e) {
                return false;
            }
        }();
        var _old$On = $.fn.on;

        $.fn.on = function () {
            if (/click/.test(arguments[0]) && typeof arguments[1] == 'function' && supportTouch) { // 只扩展支持touch的当前元素的click事件
                var touchStartY, callback = arguments[1];
                _old$On.apply(this, ['touchstart', function (e) {
                    touchStartY = e.changedTouches[0].clientY;
                }]);
                _old$On.apply(this, ['touchend', function (e) {
                    if (Math.abs(e.changedTouches[0].clientY - touchStartY) > 10) return;

                    e.preventDefault();
                    callback.apply(this, [e]);
                }]);
            } else {
                _old$On.apply(this, arguments);
            }
            return this;
        };
    };

    var androidInputBugFix = function () {
        // .container 设置了 overflow 属性, 导致 Android 手机下输入框获取焦点时, 输入法挡住输入框的 bug
        // 相关 issue: https://github.com/weui/weui/issues/15
        // 解决方法:
        // 0. .container 去掉 overflow 属性, 但此 demo 下会引发别的问题
        // 1. 参考 http://stackoverflow.com/questions/23757345/android-does-not-correctly-scroll-on-input-focus-if-not-body-element
        //    Android 手机下, input 或 textarea 元素聚焦时, 主动滚一把
        if (/Android/gi.test(navigator.userAgent)) {
            w.addEventListener('resize', function () {
                if (document.activeElement.tagName == 'INPUT' || document.activeElement.tagName == 'TEXTAREA') {
                    w.setTimeout(function () {
                        document.activeElement.scrollIntoViewIfNeeded();
                    }, 0);
                }
            })
        }
    };

    var setPageManager = function () {
        var pages = {}, tpls = $('script[type="text/html"]');
        var winH = $(w).height();

        for (var i = 0, len = tpls.length; i < len; ++i) {
            var tpl = tpls[i], name = tpl.id.replace(/tpl_/, '');
            pages[name] = {
                name: name,
                url: '#' + name,
                template: '#' + tpl.id
            };
        }
        pages.home.url = '#';

        for (var page in pages) {
            pageManager.push(pages[page]);
        }
        pageManager
            .setPageAppend(function ($html) {
                var $foot = $html.find('.page__ft');
                if ($foot.length < 1) return;

                if ($foot.position().top + $foot.height() < winH) {
                    $foot.addClass('j_bottom');
                } else {
                    $foot.removeClass('j_bottom');
                }
            })
            .setDefault('home')
            .init();
    };
    var jsapi = function () {
        w.waitloading('微信授权中');
        var msg = $('#json_wx').html();
        msg = JSON.parse(msg);
        wx.config({
            appId: msg.api,
            timestamp: msg.timestamp,
            nonceStr: msg.nonce,
            signature: msg.signature,
            jsApiList: ['openCard', 'addCard', 'scanQRCode', 'chooseWXPay']
        });
        wx.ready(function () {
            w.cancelloading();
        });
        wx.error(function (res) {
            res.url = location.href.split('#')[0];
            console.log(res);
            w.msgto2(JSON.stringify(res));
            w.location.search = '';
        });
    };
    var dict = function () {
        var hanbj = '/hanbj/';
        var mobile = 'mobile/';
        var wx = 'wxdaily/';
        var work = 'wxwork/';
        w.u1 = hanbj + mobile + 'json_addcard';
        w.u2 = hanbj + work + 'json_card';
        w.u3 = hanbj + work + 'json_act';
        w.u4 = hanbj + work + 'act_log';
        w.u5 = hanbj + mobile + 'json_active';
        w.u6 = hanbj + mobile + 'json_card';
        w.u7 = hanbj + wx + 'json_tempid';
        w.u8 = hanbj + wx + 'json_activity';
        w.u9 = hanbj + wx + 'json_valid';
        w.u10 = hanbj + wx + 'json_renew';
        w.u11 = hanbj + wx + 'fee_year';
        w.u12 = hanbj + wx + 'order';
        w.u13 = hanbj + mobile + 'json_old';
        w.u14 = hanbj + wx + 'change';
        w.u15 = hanbj + mobile + 'unused';
        w.u16 = hanbj + mobile;
        w.u17 = hanbj + wx + 'history';
        w.u18 = hanbj + wx + 'vote';
        w.u19 = hanbj + wx + 'prom';
    };
    var init = function (jssign) {
        dict();
        w.home = function () {
            location.hash = '';
        };
        w.msgto = function (jqXHR, smsg, ethrow) {
            var msg;
            try {
                msg = JSON.parse(jqXHR.responseText);
                msg = msg.msg;
            } catch (err) {
                msg = jqXHR.readyState + '-' + jqXHR.status + '-' + jqXHR.responseText + '-' + smsg + '-' + ethrow;
            }
            w.msgto2(msg);
        };
        w.msgto2 = function (msg) {
            weui.alert(msg, {title: '错误'});
        };
        w.msgok = function (data) {
            if (undefined === data) {
                data = '操作成功';
            }
            weui.toast(data, {
                duration: 2000
            });
        };
        var $loadingToast = $('#loadingToast');
        w.waitloading = function (data) {
            if (undefined === data) {
                data = '数据加载中';
            }
            loading = weui.loading(data);
        };
        w.cancelloading = function () {
            loading.hide();
        };
        fastClick();
        androidInputBugFix();
        setPageManager();
        w.pageManager = pageManager;
        if (undefined === jssign) {
            jsapi();
        }
    };
    return {
        init: init
    };
})(Zepto, window);