<?php
/**
 * Created by PhpStorm.
 * User: hjw
 * Date: 2017/9/25
 * Time: 16:29
 */

namespace Wx\Model;


use Think\Model\ViewModel;

class HotelcSaleGetmoneyViewModel extends ViewModel
{
    public $viewFields = array(
        'saleGetmoney' 	=> array('id','sno','rprice','ctime','sale_id','status','sale_id','_type'=>'left'),
    );
}