<?php

/**
 * Created by PhpStorm.
 * User: wangjianrui
 * Date: 2017/7/5
 * Time: 下午11:01
 */
class StaticPagesController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param $title
     * @return mixed
     * TODO
     */
    public function setTitle($title)
    {
        return $title;
    }

    public function home()
    {
        $this->View->renderWithoutHeaderAndFooter('static_pages/home');
    }

    public function help()
    {
        $data = array(
            'title' => $this->setTitle('帮助'),
        );
        $this->View->renderWithoutHeaderAndFooter('static_pages/help', $data);
    }

    public function about()
    {
        $data = array(
            'title' => $this->setTitle('关于'),
        );
        $this->View->renderWithoutHeaderAndFooter('static_pages/about', $data);
    }
}