<?php
/**
 * BaseController.class.php
 * 后台 首页控制器
 * @author baddl
 * @date   2017-09-04 10:08
 */
namespace Pc\Controller;
use Pc\Controller\BaseController;
use Pc\Model\MessageModel;

class IndexController extends BaseController{
	/**
	 * 初始化 
	 */
	public function _initialize(){
		//创建模型
		$this->model = 'User';
		parent::_initialize();
	}


	public function index(){
        $this->assign('server_ip',get_server_ip());
        //消息数量
        $this->get_message_num();
    	$model_left = $this->get_left_model();
        $this->assign('right_page',U($model_left[0]['method']));

		$this->display();
	}

	private function get_left_model(){
    	$module = M("Role")->field('name,oper_module')->where(array('id' => session('USERINFO.role_id')))->find();
        $module_ids = explode(',', $module['oper_module']);
        $model_left = array();
        if (!empty($module_ids)) {
            if(session('USERINFO.role_id') ==1){
                $model_left = M("Module")->field('name,module,method')->where(array('parent_id' => 0, 'status' => 1, 'id' => array('in', $module_ids)))->order('sort asc')->select();
            }else{
                $model_left = M("Module")->field('name,module,method')->where(array('parent_id' => 0, 'status' => 1,'is_show'=>1, 'id' => array('in', $module_ids)))->order('sort asc')->select();
            }
        }
        return $model_left;
    }

    /**
     * 获取当前用户消息数量
     */
    public function get_message_num(){
        $userinfo=session('USERINFO');
        //获取当前用户消息数量
        $w='(oper_url is not null and status<>-1 and find_in_set('.$userinfo['id'].',get_ids)) OR (find_in_set('.$userinfo['id'].',get_ids) and oper_url is null) OR get_ids = -1'; //
        $where['_string']= $w;
        $where['time'] = array('elt',time());
        $message = M('Message')->where($where)->count();

        //已读数量
        $num = M(MessageModel::TABLENAMELOOK)->where(array('get_id' => $userinfo['id']))->count();
        //计算数量
       $messageNum = $message*1 - $num*1;
        if ($userinfo['role_id'] != 1) {
            $this->assign('messageNum', $messageNum);
        }
    }
}