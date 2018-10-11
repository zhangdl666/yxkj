<?php
/**
 * EquipmentModel.class.php
 * 后台酒店类型
 * @author baddl
 * @date   2017-09-06 16:29
 */
namespace Pc\Model;

use Pc\Model\BaseModel;

class EquipmentModel extends BaseModel{
    protected $_validate = array(
        array('name','require','类型名称不能为空!'),
    );
    public function shuju($type){
        //根据不同类型分配不同数据
        if($type == 1){
            $name = "净化器";
        }else if($type == 2){
            $name = "监控设备";
        }else{
            $name = "中央空调品牌";
        }
        return $name;
    }
}