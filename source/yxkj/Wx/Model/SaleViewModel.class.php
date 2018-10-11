<?php
/**
 * Created by PhpStorm.
 * User: hjw
 * Date: 2017/9/26
 * Time: 16:14
 */

namespace Wx\Model;


use Think\Model\ViewModel;

class SaleViewModel extends ViewModel
{
    public $viewFields = array(
        'user' 	=> array('_table'=>'yx_user','id','name','real_name','mobile','_type'=>'left'),
        'role' 	=> array('_table'=>'yx_role','name' => 'rname','_on'=>'role.id=user.role_id'),
        'hotel'		=> array('_table'=>'yx_hotel','name'=>'hname','img' => 'himg','_on'=>'hotel.id=user.hotel_id'),
        );
}