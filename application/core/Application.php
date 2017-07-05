<?php

/**
 * Class Application
 * The heart of the application
 * 类应用程序
 * 应用程序的核心
 */
class Application
{
    /** @var mixed Instance of the controller 控制器的实例 */
    private $controller;

    /** @var array URL parameters, will be passed to used controller-method 使用URL参数,将传递给控制器方法 */
    private $parameters = array();

    /**
     * @var string Just the name of the controller, useful for checks inside the view ("where am I ?")
     * 控制器的名称,用于检查内部视图(“我在哪儿?”)
     */
    private $controller_name;

    /**
     * @var string Just the name of the controller's method, useful for checks inside the view ("where am I ?")
     * 控制器的方法的名称,用于检查内部视图(“我在哪儿?”)
     */
    private $action_name;

    /**
     * Start the application, analyze URL elements, call according controller/method or relocate to fallback location
     * 启动应用程序,分析URL元素,调用显示控制器/方法或迁往后退的位置
     */
    public function __construct()
    {
        // create array with URL parts in $url
        // 用URL部分创建数组$url
        $this->splitUrl();

        // creates controller and action names (from URL input)
        // 创建控制器和动作名称(从URL输入)
        $this->createControllerAndActionNames();

        // does such a controller exist ?
        // 这种控制器存在吗?
        if (file_exists(Config::get('PATH_CONTROLLER') . $this->controller_name . '.php')) {

            // load this file and create this controller
            // 加载这个文件并创建这个控制器
            // example: if controller would be "car", then this line would translate into: $this->car = new car();
            // 例子:如果控制器将“car”,那么这条线就会转化为:$this->car = new car();
            require Config::get('PATH_CONTROLLER') . $this->controller_name . '.php';
            $this->controller = new $this->controller_name();

            // check for method: does such a method exist in the controller ?
            // 检查方法:这种方法存在于控制器吗?
            if (method_exists($this->controller, $this->action_name)) {
                if (!empty($this->parameters)) {
                    // call the method and pass arguments to it
                    // 调用该方法,并传递参数
                    call_user_func_array(array($this->controller, $this->action_name), $this->parameters);
                } else {
                    // if no parameters are given, just call the method without parameters, like $this->index->index();
                    // 如果没有给出参数,调用该方法没有参数,如$this->index->index();
                    $this->controller->{$this->action_name}();
                }
            } else {
                // load 404 error page
                // 加载404错误页面
                require Config::get('PATH_CONTROLLER') . 'ErrorController.php';
                $this->controller = new ErrorController;
                $this->controller->error404();
            }
        } else {
            // load 404 error page
            // 加载404错误页面
            require Config::get('PATH_CONTROLLER') . 'ErrorController.php';
            $this->controller = new ErrorController;
            $this->controller->error404();
        }
    }

    /**
     * Get and split the URL
     * 获取和分裂的URL
     */
    private function splitUrl()
    {
        if (Request::get('url')) {

            // split URL
            //分裂的URL
            $url = trim(Request::get('url'), '/');
            $url = filter_var($url, FILTER_SANITIZE_URL);
            $url = explode('/', $url);

            // put URL parts into according properties
            // 把URL部分显示属性
            $this->controller_name = isset($url[0]) ? $url[0] : null;
            $this->action_name = isset($url[1]) ? $url[1] : null;

            // remove controller name and action name from the split URL
            // 把控制器名称和操作名称从分裂的URL
            unset($url[0], $url[1]);

            // rebase array keys and store the URL parameters
            // 变基数组键和存储URL参数
            $this->parameters = array_values($url);
        }
    }

    /**
     * Checks if controller and action names are given. If not, default values are put into the properties.
     * Also renames controller to usable name.
     * 检查控制器和动作名称。如果没有,默认值是属性。
     * 还可用的名称重命名控制器。
     */
    private function createControllerAndActionNames()
    {
        // check for controller: no controller given ? then make controller = default controller (from config)
        // 检查控制器:控制器给出?然后控制器=默认控制器(配置)
        if (!$this->controller_name) {
            $this->controller_name = Config::get('DEFAULT_CONTROLLER');
        }

        // check for action: no action given ? then make action = default action (from config)
        // 鉴于检查行动:不行动?然后让action =默认行动(从配置)
        if (!$this->action_name OR (strlen($this->action_name) == 0)) {
            $this->action_name = Config::get('DEFAULT_ACTION');
        }

        // rename controller name to real controller class/file name ("index" to "IndexController")
        // 将控制器名称重命名为真正的控制器类/文件名称(“index”“IndexController”)
        $this->controller_name = ucwords($this->controller_name) . 'Controller';
    }
}
