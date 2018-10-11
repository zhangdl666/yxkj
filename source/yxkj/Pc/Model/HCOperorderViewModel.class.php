<?php
/**
 * HCOperorderViewModel.class.php
 * 合同 工单  视图
 * @author  baddl
 * @date 2017-10-16 17:29
 */
namespace Pc\Model;
use Think\Model\ViewModel;

class HCOperorderViewModel extends ViewModel{
    public $viewFields = array(
        'hc' 	=> array('_table'=>'yx_hotel_contract','id','at_id','ls_id','month_price','einstall','etime','h_id','_type'=>'left'),
        'oorder'=> array('_table'=>'yx_oper_order','id'=>'oo_id','now_num','now_nume','etime'=>'oo_etime','_on'=>'hc.id=oorder.hc_id'),
        'atype' => array('_table'=>'yx_accounts_type','type'=>'at_type','price'=>'at_price','_on'=>'atype.id=hc.at_id'),
    );
}