<?php
/**
 *PromotionViewModel.class.php
 * 促销管理试图
 * @author  刘小伟
 * @date 2017-09-23 12:01
 */
namespace Wx\Model;
use Think\Model\ViewModel;

class HotelcPromotionViewModel extends ViewModel{
    public $viewFields = array(
        'promotion' 	=> array('id','h_id','title','stime','etime','content','ctime','img','status','_type'=>'left'),
       'hotelc' 	=> array('_table'=>'yx_hotel','name'=>'hotel_name','_on'=>'hotelc.id = promotion.h_id'),
    );
}