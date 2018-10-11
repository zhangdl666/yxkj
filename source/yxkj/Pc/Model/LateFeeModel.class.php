<?php
/**
 * LateFeeModel.class.php
 * @author baddl
 * @datetime 2017-12-19 16:14
 *
 */

namespace Pc\Model;
use Pc\Model\BaseModel;


class LateFeeModel extends BaseModel{

    //产生滞纳金条件验证规则
    protected $_validate = array(
        array('content', 'require', '产生滞纳金条件不能为空'),
        array('content', 'checklen', '产生滞纳金条件不能超过40个汉字',0,'callback'),
    );

    protected function checklen($data){
	    $data = trim($data);
	    if(mb_strlen($data,'utf8')>40){
	    	return false;
	    }else{
	    	return true;
	    }
	}
}