<?php
/**
 * OoUserViewModel.class.php
 * 设备操作订单  用户  视图
 * @author  baddl
 * @date 2017-09-13 14:06
 */
namespace Wx\Model;
use Think\Model\ViewModel;

class OoUserViewModel extends ViewModel{
    public $viewFields = array(
        'oorder'=> array('_table'=>'yx_oper_order','id','h_id','hc_id','sno','num','nume','now_num','now_nume','rtime','stime','etime','img','remark','type','status','_type'=>'left'),
        'user' 	=> array('real_name'=>'name','mobile','_on'=>'user.id=oorder.u_id'),
    );
}