<?php
/**
 * OoOinfoViewModel.class.php
 * 设备操作订单  设备保养维修情况  视图
 * @author  baddl
 * @date 2017-09-13 15:56
 */
namespace Wx\Model;
use Think\Model\ViewModel;

class OoOinfoViewModel extends ViewModel{
    public $viewFields = array(
        'oorder'=> array('_table'=>'yx_oper_order','id','h_id','hc_id','sno','num','nume','now_num','now_nume','rtime','stime','etime','img','remark','type','status'),
        'oinfo' => array('_table'=>'yx_oper_info','id'=>'oi_id','equipment_sno','rt_id','floor','room_sno','note','eo_id','ctime','etime'=>'oi_etime','_on'=>'oorder.id=oinfo.oo_id'),
    );
}