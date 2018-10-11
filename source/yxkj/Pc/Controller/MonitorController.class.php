<?php
/**
 * Author: ' Silent
 * Time: 2017/11/13 16:43
 */

namespace Pc\Controller;

/**
 * 监控器
 * Class MonitorController
 * @package Pc\Controller
 */
class MonitorController extends BaseController
{

    public $model = 'equipment';

    protected function _set_wheres(&$wheres)
    {
        $wheres = array('type' => 2);
    }

    public function index()
    {
        $this->assign('name','监控器');
        $this->assign('equipment','监控器');
        parent::index();
    }

    public function add()
    {
        $this->assign('type', 2);
        $this->assign('equipment', '监控器');
        $this->display('edit');
    }
}