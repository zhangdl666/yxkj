<?php
/**
 * RoomTypeModel.class.php
 * 后台客房类型
 * @author baddl
 * @date   2017-09-06 16:44
 */
namespace Pc\Model;
use Pc\Model\BaseModel;

class RoomTypeModel extends BaseModel{
	//初始化
    protected $_validate = array(
        array('name','require','菜单名不能为空!'),
        array('name','','该菜单名已经存在！',0,'unique',1),
    );
    
	/**
	 * 获取所有客房类型
	 */
	public function getList(){
        return  M('RoomType')->field('id,name')->select();
	}
}