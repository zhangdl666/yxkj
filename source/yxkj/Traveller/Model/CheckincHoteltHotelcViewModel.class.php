<?php
/**
 *PromotionViewModel.class.php
 * 促销管理试图
 * @author  刘小伟
 * @date 2017-09-23 12:01
 */
namespace Traveller\Model;
use Think\Model\ViewModel;

class CheckincHoteltHotelcViewModel extends ViewModel{
    public $viewFields = array(
        'checkinInfo' 	=> array('id','h_id','u_id','rt_id','room_sno','eoper_num','out_pm','in_humidity','out_humidity','in_pm','ctime','_type'=>'left'),
        'hotelc' 	=> array('_table'=>'yx_hotel','provice','city','county','area','name'=>'hotel_name','img','_on'=>'hotelc.id = checkinInfo.h_id'),
        'hotelt' 	=> array('_table'=>'yx_hotel_type','name'=>'type_name','_on'=>'hotelt.id = hotelc.ht_id'),
    );
}