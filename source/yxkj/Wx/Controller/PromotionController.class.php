<?php
/**
 * Created by ${CONTROLLER_NAME}.
 * @auther: 刘小伟
 * Date: 2017/9/22
 * Time: 15:29
 */
namespace Wx\Controller;
use Wx\Controller\BaseController;

class PromotionController extends BaseController{
    public function _initialize()
    {
        //创建模型
        $this->omodel = $this->model ? $this->model : CONTROLLER_NAME;
        $this->model = D($this->omodel);
        $this->assign('group_name', '/Wx/');
        $this->assign('now_module', CONTROLLER_NAME);

        $module = M("Role")->field('name,oper_module')->where(array('id' => session('USERINFO.role_id')))->find();
        $module_ids = explode(',', $module['oper_module']);
        //当前模块下可操作方法
        if (!empty($module_ids)) {
            $method = M("Module")->field('method')->where(array('module' => CONTROLLER_NAME, 'status' => 1, 'id' => array('in', $module_ids)))->select();
            $method_arr = i_array_column($method, 'method');
            $this->assign('method_arr', $method_arr);
        }

        /* 微信JS所需 */
        $wx_config = C('WEIXIN');
        $jssdk = new JSSDK($wx_config['AppID'], $wx_config['AppSecret']);
        $sign_package = $jssdk->getSignPackage();
        //file_put_contents('./weixin_config.txt', var_export($sign_package,true),FILE_APPEND);
        $this->assign($sign_package);
    }
    //首页列表数据准备
    public function index(){
        $type = I('get.type', 1, 'intval');
        //用当前时间与到期时间进行比较
        if($type == 1){
            $where['promotion.status'] = 1;
        }else{
            $where['promotion.status'] = 0;
        }
        $where['promotion.h_id'] = $_SESSION['USERINFO']['hotel_id'];
        $data = 'HotelcPromotionView';
        $data_list =D('Promotion')->getList($data,$where,$order_str='promotion.ctime desc');
        foreach ($data_list['datas'] as $key =>$val){
            $img = explode(',',$val['img']);
            $data_list['datas'][$key]['img'] =$img[0];
        }
        $this->assign('items',$data_list['datas']);
        $count = M('Promotion')->where(array('h_id'=>$_SESSION['USERINFO']['hotel_id']))->count();
        $this->assign('type',$type);
        $this->assign('count',$count);
        cookie('__forwards__', $_SERVER['REQUEST_URI']);
        $this->display();
    }
    //编辑查看准备数据准备
    public function  _before_edit_view(){
        $id = I('get.id');
        $data = D('Promotion') ->getlister($id);
        $img =explode(',',$data['img']);
        $data['img'] =$img[0];
        $data['content'] = html_entity_decode($data['content']);
        $this->assign('data',$data);
    }
    public function edit()
    {
        $this ->_before_edit_view();
        $this ->display();
    }

    //数据处理
    public function handle(){
//        $begin = $_POST['stime'];
//        $end = $_POST['etime'];
        $_POST['h_id'] =$_SESSION['USERINFO']['hotel_id'];
        $_POST['sno'] = get_reimbursement_sn();
        if (!D('Promotion')->create($_POST)){
                // 对data数据进行验证
              ajax_return(0,D('Promotion')->getError());
            }else{
//            if ($end <$begin){
//                $info = '活动结束时间不能低于开始时间';
//                ajax_return(0,$info);
//                return false;
//            }else{
                $_POST['ctime'] = time();
                if(empty($_POST['id'])){
                    $number = M('Promotion')->add($_POST);
                    $info = '添加';
                }else{
                    $number = M('Promotion')->setField($_POST);
                    $info = '修改';
                }
                if(is_numeric($number)){
                    ajax_return(1,$info.'成功', cookie('__forwards__'));
                }else{
                    ajax_return(0,$info.'失败');
                }
            }
        //}
    }
    //详情数据
    public function looked(){
        $this->_before_edit_view();
        $this ->display();
    }
    //分享出去
    public  function  share(){
       $this->_before_edit_view();
        $this ->assign('tel',C('TEL'));
        $this ->display();
    }
    //预览数据
    public function preview(){
        $this->_before_edit_view();
        $shareInfo['title'] = '促销';
        $shareInfo['summary'] = '促销分享';
        $shareInfo['web_url'] = WEB_URL;
        $this->assign($shareInfo);
        $this ->display();
    }
    //删除数据
    public function del(){
        $id = I('get.id');
        $data = D('Promotion') ->where(array('id'=>$id)) ->delete();
        if(is_numeric($data)){
            ajax_return(1,'删除成功！',cookie('__forward__'));
            return false;
        }else{
            $this->admin_ajax_return(false);
        }
    }

}