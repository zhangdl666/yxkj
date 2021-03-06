<?php
/**
 * Author: ' Silent
 * Time: 2017/11/13 16:38
 */

namespace Pc\Controller;

/**
 * 净化器类型
 * Class PurifierTypeController
 * @package Pc\Controller
 */
class PurifierTypeController extends BaseController
{
    public $model = 'equipment';

    protected function _set_wheres(&$wheres)
    {
        $wheres = array('type' => 1);
    }

    public function index()
    {
        $this->assign('name','净化器');
        $this->assign('equipment','净化器');
        parent::index(); // TODO: Change the autogenerated stub
    }

    public function add()
    {
        $this->assign('type', 1);
        $this->assign('equipment', '净化器');
        $this->display('edit');
    }
}