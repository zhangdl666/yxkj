<?php
/**
 * Created by PhpStorm.
 * User: hjw
 * Date: 2017/9/26
 * Time: 11:40
 */

namespace Wx\Controller;
use Wx\Model\SaleGetmoneyModel;

class SaleGetmoneyController extends BaseController
{
    //显示
    public function index(){
        //下拉加载数据
        $type =I('status', 1, 'intval');
        $where['saleGetmoney.status'] =$type;
        $model = 'HotelcGetmoneyView';
        cookie('__forwards__', $_SERVER['REQUEST_URI']);
        $row = $this->model->getList($model,$where,$order_str='ctime desc');
        $count=count($row['datas']);
        if($type == 1 && $count>0){
            $this->assign('flag',1);
        }
        $this->assign('items', $row['datas']);
        $this->assign('type', $type);
        $this->display('index');
    }

    //查看待付款 和已付款
    public function recordstt()
    {
        $data =SaleGetmoneyModel::inView();
        $role =$_SESSION['USERINFO']['role_id'];
        $this->assign('role', $role);
        $this->assign('data', $data);
        $this->display();
    }

    //提现打款
    public function returnedit(){
//        if($_POST['price']>$_POST['rprice']){
//            ajax_return(0,'打款金额不能超过申请金额');
//            exit;
//        }
        if($_POST['price']<= 0){
            ajax_return(0,'打款金额不能为0');
            exit;
        }
        if($_POST['img'] != ''){
            $_POST['give_img']=implode(",",$_POST['img']);
            //更新打款时间为当前时间
            $_POST['give_time'] = time();
            $_POST['status'] = 2;

            $sno = $this->model->getFieldById($_POST['id'],'sno');
            not_oper($sno,session('USERINFO.id'));
        }else{
            $info = '请上传打款凭证！';
            ajax_return(0,$info);
            return false;
        }
        if ($this->model->create() !== false) {
            if ($_POST['price']<$_POST['rprice']){
                //低于申请金额时修改扩展表里的可提现金额
                // D('SaleExt')->startTrans();
                $price = M('SaleExt')->where(array('u_id'=>$_POST['sale_id']))->field('price,all_price')->find();
                $data['price'] = ($_POST['rprice'] -$_POST['price']+$price['price']);
                $data['all_price'] = $price['all_price'] - ($_POST['rprice'] -$_POST['price']);
                M('SaleExt')->where(array('u_id'=>$_POST['sale_id']))->setField($data);
            }
            $re_status = $this->model->operation();

            $this->admin_ajax_return($re_status);
        } else {
            ajax_return(0, $this->model->getError());
        }
    }
    public function getNoHaddleNum(){
        $userInfo=session('USERINFO');
        if($userInfo['role_id'] == 6){
            $num=M('SaleGetmoney')->where(array('status'=>1))->count();
        }
        return $num;
    }

}