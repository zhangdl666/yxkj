<?php
/**
 * HotelcHotelgHotelViewModel.class.php
 * 酒店合同 酒店 酒店认领  视图
 * @author  baddl
 * @date 2017-09-05 15:06
 */
namespace Wx\Model;
use Think\Model\ViewModel;

class HotelcHotelgHotelViewModel extends ViewModel{
    public $viewFields = array(
        'hotelc' 	=> array('_table'=>'yx_hotel_contract','id','sno','name','period','ctime','stime','etime','at_id','ls_id','maintenance_id','ls_remark','img','rsinstall','reinstall','sinstall','einstall','_type'=>'left'),
        'hotel' 	=> array('id'=>'h_id','provice','city','county','area','img'=>'h_img','is_get','is_sign','name'=>'h_name','ht_id','room_num','tell'=>'h_tell','shang_name','shang_tell','all_name','all_tell','money_name','money_tell','project_name','project_tell','_on'=>'hotel.id=hotelc.h_id'),
        'hotelg' 	=> array('_table'=>'yx_hotel_get','_on'=>'hotel.id=hotelg.h_id and hotelg.is_default=1'),
        'user'		=> array('real_name'=>'uname','mobile','_on'=>'hotelg.sale_id=user.id'),
    );
}