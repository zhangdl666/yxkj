<?php
/**
 *EarningsViewModel.class.php
 * @author  hjw
 * @date 2017-10-16 17:29
 */
namespace Pc\Model;
use Think\Model\ViewModel;

class EarningsViewModel extends ViewModel{

    public $viewFields = array(
        'c' 	=> array('_table'=>'yx_channel_level','name','_type'=>'left'),
        's'=> array('_table'=>'yx_sale_ext','channel_type','_on'=>'s.cl_id = c.id '),
        'u'=> array('_table'=>'yx_user','name=>uname','_on'=>'u.id=s.u_id'),
        'e'=> array('_table'=>'yx_earnings','price','_on'=>'e.sale_id=s.u_id'),
    );
}