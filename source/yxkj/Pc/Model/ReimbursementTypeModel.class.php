<?php
/**
 * ReimbursementTypeModel.class.php
 * 后台报销类型
 * @author baddl
 * @date   2017-11-17 17:10
 */
namespace Pc\Model;
use Pc\Model\BaseModel;

class ReimbursementTypeModel extends BaseModel{
	// 自动验证定义
    protected $_validate = array(
        array('name','require','报销类型名不能为空!'),
        array('name','','报销类型名已经存在！',0,'unique',1),
    );

}