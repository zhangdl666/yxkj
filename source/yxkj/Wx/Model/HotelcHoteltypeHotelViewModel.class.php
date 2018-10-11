<?php
/**
 *PromotionViewModel.class.php
 * 酒店认领试图
 * @author  刘小伟
 * @date 2017-09-23 12:01
 */
namespace Wx\Model;
use Think\Model\ViewModel;

class HotelcHoteltypeHotelViewModel extends ViewModel{
    public $viewFields = array(
        'hotel' 	=> array('_table'=>'yx_hotel','id','name','img','_type'=>'right'),
        'hoteltype' => array('_table'=>'yx_hotel_type','name'=>'type_name','_on'=>'hoteltype.id = hotel.ht_id'),
    );
}