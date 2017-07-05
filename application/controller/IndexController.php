<?php

class IndexController extends Controller
{
    /**
     * Construct this object by extending the basic Controller class
     * 构建这个对象通过扩展基本控制器类
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Handles what happens when user moves to URL/index/index - or - as this is the default controller, also
     * when user moves to /index or enter your application at base level
     * 处理当用户移动到URL/index/index——或者——这是默认控制器,当用户移动/index或输入您的应用程序在基础水平
     */
    public function index()
    {
        $this->View->render('index/index');
    }
}
