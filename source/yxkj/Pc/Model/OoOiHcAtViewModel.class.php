<?php
/**
 * OoOiHcAtViewModel.class.php
 * 设备操作订单  设备保养维修情况  视图
 * @author  baddl
 * @date 2017-09-13 15:56
 */
namespace Pc\Model;
use Think\Model\ViewModel;

class OoOiHcAtViewModel extends ViewModel{
    public $viewFields = array(
    	'oinfo' => array('_table'=>'yx_oper_info','equipment_sno','_type'=>'left'),
        'oorder'=> array('_table'=>'yx_oper_order','hc_id','_on'=>'oorder.id=oinfo.oo_id','_type'=>'left'),
        'hc'=>array('_table'=>'yx_hotel_contract','h_id','_on'=>'hc.id=oorder.hc_id','_type'=>'left'),
        'atype'=>array('_table'=>'yx_accounts_type','_on'=>'atype.id=hc.at_id'),
    );
}