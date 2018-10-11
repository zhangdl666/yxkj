<?php
/**
 *PromotionViewModel.class.php
 * 促销管理试图
 * @author  刘小伟
 * @date 2017-09-23 12:01
 */
namespace Wx\Model;
use Think\Model\ViewModel;

class ReturnHotelHotelaHotelcViewModel extends ViewModel{
    public $viewFields = array(
        'returnc' 	=> array('_table'=>'yx_return_money','id','sno','time','price','rprice','status','ctime','get_img','give_img','mprice','mtime','rtime','sno','etime','num','_type'=>'left'),
        'hotel' 	=> array('_table'=>'yx_hotel','name','img'=>'hotel_img','_on'=>'hotel.id = returnc.h_id'),
        'hotela' 	=> array('_table'=>'yx_accounts_type','type','price'=>'type_price','_on'=>'hotela.id = returnc.at_id'),
        'hotelc' 	=> array('_table'=>'yx_hotel_contract','ls_id','sno '=>'contract_sno','name '=>'contract_name','_on'=>'hotelc.id = returnc.hc_id'),
    );
}