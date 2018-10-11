<?php
/**
 * Author: baddl
 * Time: 2017-11-29 14:53
 */

namespace Pc\Controller;
use Pc\Server\NewMessage;

class PublicController extends BaseController{
    public function _initialize(){
    	$this->model = 'User';
    	parent::_initialize();
    }

    /**
     * 头部
     */
    /*public function header(){
    	
    	$this->display();
    }*/

    /**
     * 左侧菜单
     */
    public function left(){
        $this->assign('server_ip',get_server_ip());
        $model_left = $this->get_left_model();
        $model_left = NewMessage::getLeftModule($model_left);
        $this->assign('model_left', $model_left);
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
}