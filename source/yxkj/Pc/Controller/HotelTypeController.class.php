<?php
/**
 * HotelTypeController.class.php
 * 客房类型管理
 * @author 刘小伟
 * @date   2017-09-06 16:55
 */
namespace Pc\Controller;
use Pc\Controller\BaseController;

class HotelTypeController extends BaseController{
    protected function _set_order(&$orders){
        $orders='sort,ctime desc';
    }
}