<?php
/**
 * RoleController.class.php
 * 后台员工角色管理
 * @author baddl
 * @date   2017-09-06 16:55
 */
namespace Pc\Controller;
use Pc\Controller\BaseController;

class RoleController extends BaseController{
	/**
	 * 初始化 
	 */
	public function _initialize(){
		parent::_initialize();

		$this->assign('now_module','Role');
	}

	//准备编辑之前数据
    public function _before_edit_view()
    {
        $id = I('get.id');
        if($id){
            $permission = $this->model->where(array('id'=>$id))->getField('oper_module');
            $this->assign('permission',$permission);
        }
        //查询出权限菜单
        $menu = M('Module')->field('id,parent_id,name')->where(array('status'=>1))->order('sort asc')->select();
        $this->assign('menu',json_encode($menu));
    }

}