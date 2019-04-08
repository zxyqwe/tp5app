<?php

namespace app\books\controller;

use think\Controller;
use think\exception\HttpResponseException;

class Edit extends Controller
{
    public function _empty()
    {
        $action = $this->request->action();
        if (is_file(__DIR__ . "/../tpl/edit_$action.html")) {
            throw new HttpResponseException(view($action));
        }
        abort(404, "页面不存在$action");
    }
}
