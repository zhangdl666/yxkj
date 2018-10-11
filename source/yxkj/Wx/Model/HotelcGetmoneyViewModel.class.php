<?php
/**
 * Created by PhpStorm.
 * User: hjw
 * Date: 2017/9/26
 * Time: 16:14
 */

namespace Wx\Model;


use Think\Model\ViewModel;

class HotelcGetmoneyViewModel extends ViewModel
{
    public $viewFields = array(
        'saleGetmoney' 	=> array('id','sno','rprice','ctime','sale_id','status','sale_id','_type'=>'left'),
        'user'		=> array('_table'=>'yx_user','real_name','_on'=>'saleGetmoney.sale_id=user.id'),
        );
}