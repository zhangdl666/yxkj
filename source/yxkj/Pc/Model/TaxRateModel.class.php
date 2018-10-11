<?php
/**
 * HotelTypeModel.class.php
 * 后台酒店类型
 * @author 刘小伟
 * @date   2017-12-27 16:29
 */
namespace Pc\Model;
use Pc\Model\BaseModel;

class TaxRateModel extends BaseModel{
	// 自动验证定义
    protected $_validate = array(
        array('min_price','require','税起征点不能为空!'),
        array('min_price','/^[0-9]+(.[0-9]{1,2})?$/','请重新填写税起征点!'),
        array('max_price','require','税结束点不能为空'),
        array('max_price','/^[0-9]+(.[0-9]{1,2})?$/','请重新填写税结束点!'),
        array('rate','require','税率不能为空'),
        array('rate','/^[0-9]+(.[0-9]{1,2})?$/','请重新填写税结束点!'),
    );
    

}