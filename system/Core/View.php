<?php

/**
 * 模板引擎
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: View.php 2532 2013-05-10 10:28:28Z jiangjian $
 */

class Core_View extends Yaf_View_Simple
{
    /**
     * 当前布局
     *
     * @var string
     */
    private $_layout = 'default';

    private static $_instance;
    public static function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self(rtrim(TPL_PATH, DS));
        }
        return self::$_instance;
    }

    /**
     * Assign 传参
     *
     * @param string/array $key
     * @param mixed/null $value
     * @param mixed $value
     */
    public function assign($key, $value = null)
    {
        return $value === null
            ? parent::assign($key)
            : parent::assign($key, $value);
    }

    /**
     * 渲染输出模板
     *
     * @param string $tpl
     * @param array $data
     * @return false/string
     */
    public function display($tpl, $data = array())
    {
        if (strpos($tpl, TPL_EXT) === false) {
            $tpl .= TPL_EXT;
        }

        // 设置错误提示级别
        $this->_errorReporting();

        return parent::display($tpl, $data);
    }

    /**
     * 返回输出内容（不输出到屏幕）
     *
     * @param string $tpl
     * @param array $data
     * @return string
     */
    public function render($tpl, $data = array())
    {
        if (strpos($tpl, TPL_EXT) === false) {
            $tpl .= TPL_EXT;
        }

        // 设置错误提示级别
        $this->_errorReporting();

        return parent::render($tpl, $data);
    }

    /**
     * 设置布局
     *
     * @param string $layout
     * @return $this
     */
    public function setLayout($layout)
    {
        $this->_layout = $layout;

        return $this;
    }

    /**
     * 获取布局
     *
     * @return string
     */
    public function getLayout()
    {
        return $this->_layout;
    }

    /**
     * 布局渲染
     *
     * @param string $bodyContent
     * @param array $data
     * @param bool $return 是否仅返回（不输出到屏幕）
     * @return string
     */
    public function layout($bodyContent, $data = array(), $return = false)
    {
        $method = $return ? 'render' : 'display';

        // 加载布局
        $this->assign('bodyContent', $bodyContent);
        return $this->$method('_layout/' . $this->_layout, $data);
    }

    /**
     * 设置错误提示级别
     *
     * @return void
     */
    private function _errorReporting()
    {
        // 调试报错级别
        if (isDebug()) {
            error_reporting((E_ALL | E_STRICT) ^ E_NOTICE);
        } else {
            error_reporting(0);
        }
    }

    /**
     * 委托模式（实现在视图中也可调用控制器中的方法）
     *
     * @return mixed
     */
    public function __call($method, $args)
    {
        $controller = Yaf_Registry::get('controller');

        if (method_exists($controller, $method)) {
            return call_user_func_array(array($controller, $method), $args);
        }

        throw new Core_Exception_Fatal('Undefined method in Core_View::' . $method);
    }
}