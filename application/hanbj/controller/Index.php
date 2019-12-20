<?php

namespace app\hanbj\controller;

use hanbj\ActivityOper;
use hanbj\FameOper;
use hanbj\UserOper;
use hanbj\MemberOper;
use hanbj\vote\WxOrg;
use hanbj\vote\WxVote;
use hanbj\weixin\WxHanbj;
use think\Controller;
use think\exception\HttpResponseException;
use util\BackupOper;
use util\StatOper;
use util\ValidateTimeOper;
use wxsdk\WxTokenAccess;
use wxsdk\WxTokenJsapi;
use wxsdk\WxTokenTicketapi;
use hanbj\PayoutOper;
use hanbj\TodoOper;

class Index extends Controller
{
    public function _empty()
    {
        $action = $this->request->action();
        if (is_file(__DIR__ . "/../tpl/index_$action.html")) {
            throw new HttpResponseException(view($action));
        }
        abort(404, "页面不存在$action");
    }

    public function index()
    {
        if (UserOper::VERSION === session('login')) {
            FameOper::cacheMyCurrentYear();
            return redirect('https://app.zxyqwe.com/hanbj/index/home');
        }
        return view('login');
    }

    public function old() //需要这个，不然route就会屏蔽入口
    {
        return redirect('https://app.zxyqwe.com/hanbj/pub/bulletin');
    }

    public function logout()
    {
        session(null);
        return redirect('https://app.zxyqwe.com/hanbj/index/home');
    }

    /**
     * @throws
     */
    public function cron()
    {
        local_cron();
        define('TAG_TIMEOUT_EXCEPTION', true);
        $name = 'indexHanbjCron';
        if (cache("?$name"))
            return;
        cache($name, $name, 60 - 10);
        $db = new WxTokenAccess('HANBJ_ACCESS', config('hanbj_api'), config('hanbj_secret'));
        $db->refresh();
        WxHanbj::addUnionID($db->get());
        $db = new WxTokenJsapi('HANBJ_JSAPI', config('hanbj_api'), config('hanbj_secret'));
        $db->refresh();
        $db = new WxTokenTicketapi('HANBJ_TICKETAPI', config('hanbj_api'), config('hanbj_secret'));
        $db->refresh();
        MemberOper::daily();

        PayoutOper::generateAnyTodo();
        PayoutOper::handleOneAuth();
        PayoutOper::notify_original();


        // 2点以后
        if (ValidateTimeOper::GoodForBackup()) {
            StatOper::generateOneDay(StatOper::LOG_NUM);
            StatOper::generateOneDay(StatOper::HANBJ_ORDER_NUM);
            ActivityOper::revokeTest();
        }

        // 白天
        if (ValidateTimeOper::IsDayUp()) {
            BackupOper::run();
            TodoOper::noticeAny();
        }

        if (ValidateTimeOper::IsYearEnd()) {
            WxVote::try_add_todo();
            foreach (WxOrg::vote_cart as $item) {
                $org = new WxOrg(intval($item));
                $org->try_add_todo();
            }
        }
        WxOrg::cancel_all_todo();
        WxVote::cancel_all_todo();
    }
}
