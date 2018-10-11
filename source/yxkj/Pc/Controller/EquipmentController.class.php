<?php
/**
 * HotelTypeController.class.php
 * 净化器类型管理
 * @author 刘小伟
 * @date   2017-09-08 16:55
 */
namespace Pc\Controller;

class EquipmentController extends BaseController{
    protected function _set_wheres(&$wheres){
        //查询条件
        $id =I('id');
        $wheres = array(type=>$id);
    }
    public function add(){
        //根据不同类型分配不同数据
        $type =I('id');
        $equipment =D('Equipment') ->shuju($type);
        $this ->assign('type',$type);
        $this ->assign('equipment',$equipment);
        $this ->display('edit');
    }
}