<?php
/**
 * HotelTypeModel.class.php
 * 后台酒店类型
 * @author baddl
 * @date   2017-09-06 16:29
 */
namespace Wx\Model;
use Wx\Model\BaseModel;

class HotelTypeModel extends BaseModel{
	// 自动验证定义
    protected $_validate = array(
        array('name','require','酒店类型名不能为空!'),
        array('name','','名称已经存在！',0,'unique',1),
    );
    
	/**
	 * 获取所有酒店类型
	 */
	public function getList(){
		$list = $this->field('id,name')->order('id desc')->select();
		return $list;
	}
}