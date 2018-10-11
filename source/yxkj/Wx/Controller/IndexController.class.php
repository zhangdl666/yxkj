<?php
/**
 * IndexController.class.php
 * 后台 首页
 * @author baddl
 * @date   2017-09-18 13:59
 */
namespace Wx\Controller;
use Wx\Controller\BaseController;
use Pc\Model\MessageModel;
class IndexController extends BaseController{
	/**
	 * 初始化 
	 */
	public function _initialize(){
		$this->model = 'User';
		parent::_initialize();
		$module = M("Role")->field('name,oper_module')->where(array('id'=>session('USERINFO.role_id')))->find();
		$module_ids = explode(',',$module['oper_module']);
		$model_left = array();
		if(!empty($module_ids)){
			$model_left = M("Module")->field('name,module,method')->where(array('parent_id'=>0,'status'=>1,'id'=>array('in',$module_ids)))->order('sort asc')->select();
			if(!empty($model_left)){
				$num = 0;
				foreach($model_left as $value){
					if(in_array($value['module'],array('PlatformUser','CheckinInfo','Role','Message','AlarmPm25'))){
						continue;
					}
					$model_arr[$num] = $value;
					if(!in_array($value['module'],array('Hotel','Sales','HotelLogged','RunInfo','RepairCount'))){
                        //$model_arr[$num]['name'] .= '待办事项';
                        $model_arr[$num]['name'];
                    }
					//获取模块对应的待办事项数量
					$controllerName = strstr($value['method'], '/', TRUE);
					//$value['number'] = R($controllerName)->getNoHaddleNum();
                    if(session('USERINFO.role_id') != 9){
                        $model_arr[$num]['number'] = R($controllerName.'/getNoHaddleNum');
                    }
					++$num;
				}
			}
		}
		$this->assign('module',$model_arr);

	}

	public function index(){
	    $userinfo=session('USERINFO');
        /*$w='(oper_url is not null and status<>-1 and find_in_set('.$userinfo['id'].',get_ids)) OR (find_in_set('.$userinfo['id'].',get_ids) and oper_url is null) OR get_ids = -1';
        $where['_string']= $w;
        $where['time'] = array('elt',time());
        $message = M('Message')->where($where)->count();
        $num = M(MessageModel::TABLENAMELOOK)->where(array('get_id' => $userinfo['id']))->count();
        $messageNum = $message*1 - $num*1;*/
        $messageNum = $this->getMessageNum($userinfo['id']);
        if ($messageNum != 0 && $userinfo['role_id'] != 1) {
            $this->assign('flag',1);
        }else{
            $this->assign('flag',0);
        }
        $this->assign('user_id',$userinfo['id']);

        $this->assign('server_ip',get_server_ip());
		$this->display();
	}

	/**
	 * 上传图片测试
	 */
	public function img(){
		$appid = C('WEIXIN.AppID');
        $appSecret = C('WEIXIN.AppSecret');
        $jssdk = new JSSDK($appid,$appSecret);
        $sign_package = $jssdk->getSignPackage();
        $this->assign($sign_package);

        $this->display();
	}

	/**
	 * 硬件设备授权
	 */
	/*public function device(){
		$access_token = $this->get_access_token();
		$device_impower_url = 'https://api.weixin.qq.com/device/getqrcode?access_token='.$access_token.'&product_id=42289';
		
		$re_data = $this->http_curl($device_impower_url);
		$re_data = json_decode($re_data);
		print_r($re_data);
	}*/

	/**
	 * 设备解绑
	 */
	/*public function unbind_device(){
		$access_token = $this->get_access_token();
		$unbind_url = 'https://api.weixin.qq.com/device/compel_unbind?access_token='.$access_token;
		$data = array('device_id'=>'gh_565eea186c8d_ca452f92b830b822','openid'=>'oVma40iGulbthqYEfgSuy80KQLp0');
		$data = json_encode($data);

		$re_data = $this->http_curl($unbind_url,$data);
		$re_data = json_decode($re_data);
		file_put_contents('./unbind_device.txt', var_export($re_data,true)."\r\n",FILE_APPEND);
	}*/



    public function getMessageNum($id=null){
        $id = $id ? $id : I('post.id');
        $w='(oper_url is not null and status<>-1 and find_in_set('.$id.',get_ids)) OR (find_in_set('.$id.',get_ids) and oper_url is null) OR get_ids = -1';
        $where['_string']= $w;
        $where['time'] = array('elt',time());
        $message = M('Message')->where($where)->count();
        $num = M(MessageModel::TABLENAMELOOK)->where(array('get_id' => $id))->count();
        $messageNum = $message*1 - $num*1;
        if(I('post.id')){
        	$this->ajaxReturn(array('code'=>1,'message'=>$messageNum));
        }else{
        	return $messageNum;
        }
        
    }
}