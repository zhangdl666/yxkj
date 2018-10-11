<?php
/**
 * HotelgUserViewModel.class.php
 * 酒店 酒店认领  视图
 * @author  baddl
 * @date 2017-09.05 11:34
 */
namespace Pc\Model;
use Think\Model\ViewModel;

class HotelgUserViewModel extends ViewModel{
    public $viewFields = array(
        'hotelg'=> array('_table'=>'yx_hotel_get','_type'=>'left'),
    	'User'	=> array('name','_on'=>'user.id=hotelg.sale_id and hotelg=0'),
    );
}