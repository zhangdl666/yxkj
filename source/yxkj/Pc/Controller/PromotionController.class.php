<?php
/**
 * PromotionController.class.php
 * 结算
 * @author 刘小伟
 * @date   2017-09-18 10:02
 */
namespace Pc\Controller;

class PromotionController extends BaseController{
    //首页列表数据准备
    public function index()
    {
        $user = $_SESSION['USERINFO']['role_id'];
        $p = I('get.p', 1, 'intval');
        $data = D('Promotion') ->getall($p);
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->assign('items', $data['items']);
        $count =count($data['items']);
        $this->assign('count', $count);
        $this->assign('user', $user);
        $this->assign('pageHtml', $data['pageHtml']);
        $this ->display();
    }
    //编辑查看准备数据准备
    public function  _before_edit_view(){
        $id = I('get.id');
        $data = D('Promotion') ->getlist($id);
        $this->assign('data',$data);
    }
    public function look(){
        $id = I('get.id');
        $data = D('Promotion') ->getlist($id);
        $data['content'] = html_entity_decode($data['content']);
        $this->assign('data',$data);
        $this ->display();
    }


    //数据处理
    public function handle(){
        $num = count(array_keys($_POST));
        if($num > 8){
            ajax_return(0,'输入内容有误，请重新提交');
              return false;
        }
//        $_POST['content'] =str_replace(' ','&nbsp',$_POST['content']);
//        $begin = $_POST['stime'];
//        $end = $_POST['etime'];
        $_POST['h_id'] =$_SESSION['USERINFO']['hotel_id'];
        $_POST['sno'] = get_reimbursement_sn();
        if (!D('Promotion')->create($_POST)){
            // 对data数据进行验证
            ajax_return(0,D('Promotion')->getError());
            return false;
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
                    ajax_return(1,$info.'成功', cookie('__forward__'));
                }else{
                    ajax_return(0,$info.'失败');
                }
           // }
        }
    }
    //删除数据
    public function looked(){
        $id = I('get.id');
        $data = D('Promotion') ->where(array('id'=>$id)) ->delete();
        if(is_numeric($data)){
            ajax_return(1,'删除成功！',cookie('__forward__'));
        }else{
            $this->admin_ajax_return(false);
        }
    }

}
