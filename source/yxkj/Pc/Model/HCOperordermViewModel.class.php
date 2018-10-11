<?php
/**
 * HCOperordermViewModel.class.php
 * 合同 工单  视图
 * @author  baddl
 * @date 2017-10-16 17:29
 */
namespace Pc\Model;
use Think\Model\ViewModel;

class HCOperordermViewModel extends ViewModel{
    public $viewFields = array(
        'hc' 	=> array('_table'=>'yx_hotel_contract','id','einstall','h_id','_type'=>'left'),
        'oorder'=> array('_table'=>'yx_oper_order','id'=>'oo_id','now_num','now_nume','_on'=>'hc.id=oorder.hc_id'),
        'maintenance'=> array('num','_on'=>'hc.maintenance_id=maintenance.id'),
    );
}