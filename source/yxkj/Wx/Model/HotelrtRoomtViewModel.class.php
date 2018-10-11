<?php
/**
 * HotelrtRoomtViewModel.class.php
 * 酒店客房类型 酒店房间数  视图
 * @author  baddl
 * @date 2017-09.05 11:34
 */
namespace Wx\Model;
use Think\Model\ViewModel;

class HotelrtRoomtViewModel extends ViewModel{
    public $viewFields = array(
        'hotelt' 	=> array('_table'=>'yx_room_type','id','name','_type'=>'left'),
        'hotelrt' 	=> array('_table'=>'yx_hotel_room_type','room_num','_on'=>'hotelt.id=hotelrt.rt_id'),
    );
}