<?php
/**
 * BaseController.class.php
 * 后台公共控制器
 * @author baddl
 * @date   2017-09-04 09:48
 */

namespace Pc\Controller;

use Pc\Model\UserObjModel;
use Pc\Server\NewMessage;
use Think\Controller;
use Pc\Model\MessageModel;

class BaseController extends Controller
{
    protected $model;
    //设置查询字段
    protected $query = 'name';
    protected $placeholder;

    /**
     * 初始化
     */
    public function _initialize()
    {
        $this->model = D($this->model ? $this->model : CONTROLLER_NAME);
        $this->assign('group_name', '/Pc/');

        $session = session('USERINFO');
        $cookie = cookie('USERINFO');
        if (empty($session)) {
            if (!empty($cookie)) {
                session('USERINFO', $cookie);
            } else {
                header('Location: ' . U('Login/index'));
                exit;
            }
        }

        //用户信息
        $userinfo = session('USERINFO');
        $this->assign('ip','http://'.get_client_ip().':2129');
        $userinfo = M('User')->field('id,real_name,img,role_id')->getById($userinfo['id']);
        $this->assign('username', $userinfo['real_name']);
        $this->assign('userimg', $userinfo['img']);
        $this->assign('user_id', $userinfo['id']);
        $this->assign('role', $userinfo['role_id']);

        $module = M("Role")->field('name,oper_module')->where(array('id' => $userinfo['role_id']))->find();
        $module_ids = explode(',', $module['oper_module']);

        /*if(CONTROLLER_NAME == 'Index') {
          $this->assign('defaulturl',U($model_left[0]['method']));
          $this->assign('now_module',$model_left[0]['module']);
        }else{
            $this->assign('now_module', CONTROLLER_NAME);
        }*/
        $this->assign('now_module', CONTROLLER_NAME);

        //当前模块下可操作方法
        if (!empty($module_ids)){
            $method = M("Module")->field('method')->where(array('module' => CONTROLLER_NAME, 'status' => 1, 'id' => array('in', $module_ids)))->select();
            $method_arr = i_array_column($method, 'method');
            /*if(!in_array(CONTROLLER_NAME.'/'.ACTION_NAME, $method_arr)){
                //AJAX请求
                if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'){
                    ajax_return(0,'此用户无操作权限,请重新登录');
                    exit;
                }else{
                    header('Content-Type: text/html; charset=UTF-8');
                    die('此用户无此操作权限,请重新登录');
                }
            }*/
            $this->assign('method_arr', $method_arr);
        }

        //皮肤
        $skin = cookie('SKIN') ? cookie('SKIN') : 'a';
        $this->assign('skin',$skin);
	}

	/**
	 * 获取列
	 */
	public function index(){
		//条件
		$wheres = array();
		$keyword = trim(I('get.keyword'));
		$kstatus = I('get.kstatus');
		//关键词
		if(!empty($keyword)){
			$wheres[$this->query] = array('like','%'.$keyword.'%');
			$this->assign('keyword',$keyword);
		}

		//状态
		if($kstatus == '0' || !empty($kstatus)){
			$wheres['status'] = $kstatus;
			$this->assign('kstatus',$kstatus);
		}else{
			$wheres['status'] = array('neq','-1');
		}
		//查询条件
		$this->_set_wheres($wheres);
		$this->_set_order($orders);
		//数据查询
		$re_datas = $this->model->getResultList($wheres,$orders);
		$this->assign($re_datas);
		//列表页面展示之前准备数据
		$this->_before_index_view();
		//将当前列表的url保存到cookie中,为了 删除,添加,修改状态,编辑 之后跳转到该地址
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->assign('placeholder', $this->placeholder);

        $this->display();
    }

    /**
     * 添加数据
     */
    public function add()
    {
        //添加/编辑展示之前准备数据
        $this->_before_edit_view();

        $this->display('edit');
    }

    /**
     * 编辑数据
     */
    public function edit()
    {
        //添加/编辑展示之前准备数据
        $this->_before_edit_view();

        //获取详情
        $id = I('get.id');
        $info = $this->model->getInfo($id);
        $this->assign($info);
        $redonly = I('get.redonly');
        if ($redonly) {
            $this->assign('redonly', $redonly);
        }

        $this->display();
    }

    /**
     * 对数据进行操作
     */
    public function operation()
    {
        if ($this->model->create() !== false) {
            $re_status = $this->model->operation();
            /*$UserObjData = new UserObjModel();
            $roleId = $UserObjData->getRoleId();
            $UserId = $UserObjData->getUserId();
            // 平台销售添加/编辑合同->在线平台工程经理发送相关提醒
            if($roleId == 2){
                $postdata = $this->model->create();
                if($postdata['price']){
                    $user_id = M('User')->where(array('id'=>$UserId))->getField('parent_id');
                    if($user_id){
                        NewMessage::remind(10,$user_id);
                    }
                }
                NewMessage::remind(1);
            }
            // 酒店财务经理->给平台销售经理提醒
            if($roleId == 10){
                NewMessage::remind(8);
            }
            //给在线平台工程人员发送相关提醒
            if ($roleId == 5) {
                $postdata = $this->model->create();
                NewMessage::remind(2, $postdata['u_id']);
            }
            // 給平台财务确认到账->提醒
            if($roleId == 10){
                NewMessage::remind(8);
            }*/
            $this->admin_ajax_return($re_status);
        } else {
            ajax_return(0, $this->model->getError());
        }
    }

    /**
     * 删除
     * @param  string $ids IDs号
     * @return array
     */
    public function del($ids)
    {
        $re_status = $this->model->delete_data($ids);

        $this->admin_ajax_return($re_status);
    }

    /**
     * 改变状态
     * @param  string $ids 要操作的ID号
     * @param  int $status 状态
     */
    public function change_status($ids, $status = '-1')
    {
        $re_status = $this->model->changeStatus($ids, $status);
        $this->admin_ajax_return($re_status);
    }

    /**
     * AJAX 返回
     * @param  int $re_status
     */
    protected function admin_ajax_return($re_status)
    {
        if ($re_status != 0) {
            ajax_return(1, '操作成功！', cookie('__forward__'));
        } else {
            $info = $this->model->getError() ? $this->model->getError() : '操作失败！';
            ajax_return(0, $info);
        }
    }

    /**
     * 提供查询条件
     * @param  array $wheres 查询条件
     */
    protected function _set_wheres(&$wheres)
    {

    }
    /**
     * 提供排序方式
     * @param string $orders 排序方式
     */
    protected function _set_order(&$orders){

    }
    /**
     * 列表页面展示之前准备数据
     */
    protected function _before_index_view()
    {

    }

    /**
     * 添加/编辑展示之前准备数据
     */
    protected function _before_edit_view()
    {

    }

    /**
     * 文件上传
     */
    public function upload_file()
    {
        $img_dir = I('get.img_dir');
        $result = upload($img_dir);
        echo json_encode($result);
        exit;
    }

    /**
     * 文件下载
     * @param  string $filename 下载的文件名
     * @param  string $showname 显示的文件名
     */
    public function download_file($filename, $showname = '')
    {
        download($filename, $showname);
    }

	/**
	 * 切换皮肤
	 */
	public function upskin(){
		$skin = I('post.skin');
		cookie('SKIN',$skin);
		ajax_return(1,'皮肤切换成功',$_SERVER['HTTP_REFERER']);
	}


}