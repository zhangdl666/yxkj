<?php
/**
 * HoteltHcreViewModel.class.php
 * 客房类型 合同房间类型房间数  视图
 * @author  baddl
 * @date 2017-09-09 18:09
 */
namespace Wx\Model;
use Think\Model\ViewModel;

class HoteltHcreViewModel extends ViewModel{
    public $viewFields = array(
        'hotelt' 	=> array('_table'=>'yx_room_type','id','name','_type'=>'left'),
        'hcre' 	=> array('_table'=>'yx_hc_room_equipment','r_num','e1_id','e1_num','e2_id','e2_num','_on'=>'hotelt.id=hcre.rt_id'),
    );
}