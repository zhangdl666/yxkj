<?php
/**
 * PlatformUserController.class.php
 * 后台 平台工作人员
 * @author baddl
 * @date   2017-09-14 09:31
 */
namespace Pc\Controller;
use Pc\Controller\BaseController;

class PlatformUserController extends BaseController{
	/**
	 * 初始化 
	 */
	public function _initialize(){
		$this->model = 'User';
		parent::_initialize();
		$this->placeholder = '请输入用户姓名/账号';
	}

	/**
	 * 提供查询条件
	 * @param  array  $wheres 查询条件
	 */
	protected function _set_wheres(&$wheres){
        $name = I('post.name');
        $role_id = I('post.role_id');
        if(!empty($role_id)) {
            $wheres['role_id']= $role_id;
            $this->assign('wrole',$role_id);
        }
        if(!empty($name)) {
            $wheres['real_name']=array('like','%'.$name.'%');
            $this->assign('wname',$name);
        }
        $wheres['status'] = 1;
		$wheres['type'] = 1;
	}

	/**
	 * 编辑前准备工作
	 */
	protected function _before_index_view(){
		$role_list = M('Role')->field('id,name')->where(array('type'=>1))->order('sort asc')->select();
		$this->assign('role_list',$role_list);
	}


	/**
	 * 编辑前准备工作
	 */
	protected function _before_edit_view(){
		$role_list = M('Role')->field('id,name')->where(array('type'=>1))->order('sort asc')->select();
		$this->assign('role_list',$role_list);

		if(I('get.id')){
			$channel_type = M('SaleExt')->where(array('u_id'=>I('get.id')))->getField('channel_type');
			$this->assign('channel_type',$channel_type);
		}
		
	}

	/**
	 * 获取上级的所有领导
	 */
	public function get_parents(){
		$rid = I('post.rid');
		if($rid == 2){
			$prid = 3;
		}elseif($rid == 4){
			$prid = 5;
		}
		$rname = M('Role')->getFieldById($prid,'name');
		$users = M('User')->field('id,real_name as name')->where(array('role_id'=>$prid,'status'=>1))->select();
		
		ajax_return(1,'获取成功',array('rname'=>$rname,'pusers'=>$users));
	}

    //解除微信绑定
    public function wxdel(){
        $model=M('user')->where(array('id'=>$_GET['id']));
        $model->uuid=null;
        $model->save();
        $this->redirect('index');
    }
}