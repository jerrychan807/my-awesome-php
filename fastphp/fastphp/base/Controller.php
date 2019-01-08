<?php
namespace fastphp\base;

/*
 * 控制器基类
 * Controller 类用assign()方法实现把变量保存到View对象中。
 * 这样，在调用$this->render() 后视图文件就能显示这些变量。
 *
 */


class Controller
{
    protected $_controller;
    protected $_action;
    protected $_view;

    // 构造函数, 初始化属性, 并实例化对应模型
    public function __construct($controller, $action)
    {
        $this->_controller = $controller;
        $this->_action = $action;
        $this->_view = new View($controller, $action);
    }

    // 分配变量
    public function assign($name, $value){
        $this->_view->assign($name, $value);
        // assign(): hw_api_object::assign — Clones object
    }

    // 渲染视图
    public function render(){
        $this->_view->render();
    }

}