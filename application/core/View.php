<?php

/**
 * Class View
 * The part that handles all the output
 * 类视图
 * 处理所有输出的一部分
 */
class View
{
    /**
     * simply includes (=shows) the view. this is done from the controller. In the controller, you usually say
     * $this->view->render('help/index'); to show (in this example) the view index.php in the folder help.
     * Usually the Class and the method are the same like the view, but sometimes you need to show different views.
     * 仅仅包括(=shows)视图。这从控制器完成。你通常说,在控制器中$this->view->render('help/index');
     * 显示(在这个例子中)视图index.php在文件夹的帮助。通常的类和方法都是相同的视图,但有时你需要显示不同的观点。
     * @param string $filename Path of the to-be-rendered view, usually folder/file(.php)
     * 呈现视图的路径,通常folder/file(.php)
     * @param array $data Data to be used in the view 在视图中使用的数据
     */
    public function render($filename, $data = null)
    {
        if ($data) {
            foreach ($data as $key => $value) {
                $this->{$key} = $value;
            }
        }

        require Config::get('PATH_VIEW') . '_templates/header.php';
        require Config::get('PATH_VIEW') . $filename . '.php';
        require Config::get('PATH_VIEW') . '_templates/footer.php';
    }

    /**
     * Similar to render, but accepts an array of separate views to render between the header and footer. Use like
     * the following: $this->view->renderMulti(array('help/index', 'help/banner'));
     * 呈现类似,但接受一系列单独的页眉和页脚之间的观点呈现。使用如下:$this->view->renderMulti(array('help/index', 'help/banner'));
     * @param array $filenames Array of the paths of the to-be-rendered view, usually folder/file(.php) for each
     * 数组的路径呈现视图,通常folder/file(.php)
     * @param array $data Data to be used in the view 在视图中使用的数据
     * @return bool
     */
    public function renderMulti($filenames, $data = null)
    {
        if (!is_array($filenames)) {
            self::render($filenames, $data);
            return false;
        }

        if ($data) {
            foreach ($data as $key => $value) {
                $this->{$key} = $value;
            }
        }

        require Config::get('PATH_VIEW') . '_templates/header.php';

        foreach ($filenames as $filename) {
            require Config::get('PATH_VIEW') . $filename . '.php';
        }

        require Config::get('PATH_VIEW') . '_templates/footer.php';
    }

    /**
     * Same like render(), but does not include header and footer
     * 同样喜欢render(),但不包括页眉和页脚
     * @param string $filename Path of the to-be-rendered view, usually folder/file(.php)
     * 呈现视图的路径,通常folder/file(.php)
     * @param mixed $data Data to be used in the view 在视图中使用的数据
     */
    public function renderWithoutHeaderAndFooter($filename, $data = null)
    {
        if ($data) {
            foreach ($data as $key => $value) {
                $this->{$key} = $value;
            }
        }

        require Config::get('PATH_VIEW') . $filename . '.php';
    }

    /**
     * Renders pure JSON to the browser, useful for API construction
     * 向浏览器呈现纯JSON,有用的API建设
     * @param $data
     */
    public function renderJSON($data)
    {
        header("Content-Type: application/json");
        echo json_encode($data);
    }

    /**
     * renders the feedback messages into the view
     * 呈现的反馈信息到视图中
     */
    public function renderFeedbackMessages()
    {
        // echo out the feedback messages (errors and success messages etc.),
        // 回声的反馈信息(错误和成功消息等),
        // they are in $_SESSION["feedback_positive"] and $_SESSION["feedback_negative"]
        // 他们在$_SESSION["feedback_positive"]和$_SESSION["feedback_negative"]
        require Config::get('PATH_VIEW') . '_templates/feedback.php';

        // delete these messages (as they are not needed anymore and we want to avoid to show them twice
        // 删除这些消息(因为他们不需要了,我们要避免两次给他们看
        Session::set('feedback_positive', null);
        Session::set('feedback_negative', null);
    }

    /**
     * Checks if the passed string is the currently active controller.
     * Useful for handling the navigation's active/non-active link.
     * 检查传递的字符串是当前活跃的控制器。用于处理导航的主动/稳定的链接。
     *
     * @param string $filename
     * @param string $navigation_controller
     *
     * @return bool Shows if the controller is used or not 显示了如果使用控制器
     */
    public static function checkForActiveController($filename, $navigation_controller)
    {
        $split_filename = explode("/", $filename);
        $active_controller = $split_filename[0];

        if ($active_controller == $navigation_controller) {
            return true;
        }

        return false;
    }

    /**
     * Checks if the passed string is the currently active controller-action (=method).
     * Useful for handling the navigation's active/non-active link.
     * 检查传递的字符串是当前活跃的控制器动作(=method)。用于处理导航的主动/稳定的链接。
     *
     * @param string $filename
     * @param string $navigation_action
     *
     * @return bool Shows if the action/method is used or not 显示如果操作/使用方法
     */
    public static function checkForActiveAction($filename, $navigation_action)
    {
        $split_filename = explode("/", $filename);
        $active_action = $split_filename[1];

        if ($active_action == $navigation_action) {
            return true;
        }

        return false;
    }

    /**
     * Checks if the passed string is the currently active controller and controller-action.
     * Useful for handling the navigation's active/non-active link.
     * 检查传递的字符串是当前活跃的控制器和控制器动作。用于处理导航的主动/稳定的链接。
     *
     * @param string $filename
     * @param string $navigation_controller_and_action
     *
     * @return bool
     */
    public static function checkForActiveControllerAndAction($filename, $navigation_controller_and_action)
    {
        $split_filename = explode("/", $filename);
        $active_controller = $split_filename[0];
        $active_action = $split_filename[1];

        $split_filename = explode("/", $navigation_controller_and_action);
        $navigation_controller = $split_filename[0];
        $navigation_action = $split_filename[1];

        if ($active_controller == $navigation_controller AND $active_action == $navigation_action) {
            return true;
        }

        return false;
    }

    /**
     * Converts characters to HTML entities
     * This is important to avoid XSS attacks, and attempts to inject malicious code in your page.
     * 将字符转换为HTML实体 这是很重要的,以避免XSS攻击,试图注入恶意代码在你的页面。
     *
     * @param  string $str The string. 的字符串。
     * @return string
     */
    public function encodeHTML($str)
    {
        return htmlentities($str, ENT_QUOTES, 'UTF-8');
    }
}
