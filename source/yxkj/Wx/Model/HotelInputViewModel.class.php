<?php
/**
 * Created by PhpStorm.
 * User: hjw
 * Date: 2017/9/26
 * Time: 16:14
 */

namespace Wx\Model;


use Think\Model\ViewModel;

class HotelInputViewModel extends ViewModel
{
    public $viewFields = array(
        'hotel' 	=> array('_table'=>'yx_hotel','id','sn','img','name','is_get','_type'=>'left'),
        'hotel_type' 	=> array('_table'=>'yx_hotel_type','name' => 'tname','_on'=>'hotel.ht_id=hotel_type.id'),
        );
}