<?php
/**
 * Created by ${CONTROLLER_NAME}.
 * @auther: 刘小伟
 * Date: 2017/12/27
 * Time: 18:01
 */
namespace Pc\Controller;

class TaxRateController extends BaseController
{
public function _before_operation()
{
    if($_POST['max_price'] <= $_POST['min_price']){
        ajax_return(0,'税结束点必须大于税起征点');
        exit;
    }
    $data = $this ->model ->select();
    if(!empty($_POST['id'])){
        $id = $_POST['id'];
        $data = M('TaxRate') ->where(array('id'=>array('neq',$id)))->select();
    }
    foreach ($data as $key=>$val){
        if($_POST['max_price']>=$val['min_price'] && $_POST['max_price']<=$val['max_price']){
            ajax_return(0,'请重新设置税结束点');
            exit;
        }
        if($_POST['min_price']>=$val['min_price'] && $_POST['min_price']<=$val['max_price']){
            ajax_return(0,'请重新设置税起征点');
            exit;
        }
    }

    if($_POST['rate']<=0){
        ajax_return(0,'税率必须大于0');
        exit;
    }
    if($_POST['rate']>10000){
        ajax_return(0,'税率不能大于10000');
        exit;
    }
}


}