<?php
/**
 * OoOiHHcViewModel.class.php
 * 设备信息 订单 酒店 合同
 * @author  baddl
 * @datetime 2018-01-29 23:04
 */
namespace Pc\Model;
use Think\Model\ViewModel;

class OoOiHHcViewModel extends ViewModel{
    public $viewFields = array(
        'oinfo' => array('_table'=>'yx_oper_info','id','room_sno','e_id','equipment_sno','rt_id','floor','is_window','orientation','place','e_id','_type'=>'left'),
        'oorder'=> array('_table'=>'yx_oper_order','_on'=>'oinfo.oo_id = oorder.id','_type'=>'left'),
        'hc'	=> array('_table'=>'yx_hotel_contract','name'=>'hc_name','_on'=>'oorder.hc_id=hc.id','_type'=>'left'),
        'hotel'	=> array('name'=>'hotel_name','_on'=>'hotel.id=oorder.h_id and hotel.id=hc.h_id','_type'=>'left'),
    	'equipment'=>array('name'=>'equipment_name','_on'=>'oinfo.e_id=equipment.id'),
    );
}