<?php
/**
 * HotelGetHotelViewModel.class.php
 * 酒店 酒店认领  视图
 * @author  baddl
 * @date 2017-09.05 11:34
 */
namespace Pc\Model;
use Think\Model\ViewModel;

class HotelGetHotelViewModel extends ViewModel{
    public $viewFields = array(
        'hotel' 	=> array('id','name','_type'=>'left'),
        'hotelg' 	=> array('_table'=>'yx_hotel_get','_on'=>'hotel.id=hotelg.h_id'),
    );
}