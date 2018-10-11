<?php
/**
 * HotelTypeController.class.php
 * 净化器类型管理
 * @author 刘小伟
 * @date   2017-09-08 16:55
 */
namespace Pc\Controller;

class EquipmentOperController extends BaseController{
    protected function _set_wheres(&$wheres){
        //查询条件
        $id =I('type');
        $wheres = array('type'=>$id);
        $leixin =($id ==1)?"保养":"维修";
        $this ->assign('leixin',$leixin);
    }

}