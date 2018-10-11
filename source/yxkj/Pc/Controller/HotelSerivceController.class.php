<?php
/**
 * Created by PhpStorm.
 * User: hjw
 * Date: 2017/9/9
 * Time: 14:08
 */

namespace Pc\Controller;


class HotelSerivceController extends BaseController
{

    //查看功能
    public function sel(){
        $id = I('get.id');
        $model=M('HotelSerivce')->where(array('id'=>$id))->find();
        $this->assign('model',$model);
        $this->display();
    }
}