<?php
/**
 * HotelContractController.class.php
 * 结算
 * @author 刘小伟
 * @date   2017-09-11 18:02
 */
namespace Pc\Controller;
use Pc\Model\SaleGetmoneyModel;

class SaleGetmoneyController extends BaseController{

	/**
	 * 列表数据
	 */
    public function index()
    {
        $roth =I('get.id');
        if($roth ==0){
            $rother = array();
            $type = '全部';
        }else{
            $rother =$roth;
            if($rother == 1){
                $type = '待打款';
            }else{
                $type = '已打款';
            }
        }
        $p = I('get.p', 1, 'intval');
        $count =D('SaleGetmoney')->counter();
        $data =D('SaleGetmoney')->getResultLister($rother,$p);
        //将当前列表的url保存到cookie中,为了 删除,添加,修改状态,编辑 之后跳转到该地址
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->assign('items', $data['items']);
        $this->assign('count', $count);
        $this->assign('type', $type);
        $this->assign('pageHtml', $data['pageHtml']);
        $this->display();
    }
    /*
     * 打款之前准备数据
     * */
    public function settlement(){
        $data =SaleGetmoneyModel::inView();
        $role = $_SESSION['USERINFO']['role_id'];
        $this ->assign('role',$role);
        $this ->assign('data',$data);
        $this ->display();
    }
    /*
         * 到账确认之前准备数据
         * */
    public function confirmation(){
        $data =SaleGetmoneyModel::inView();
        $this ->assign('data',$data);
        $this ->display();
    }
    /*
         * 查看之前准备数据
         * */
    public function looked(){
        $data =SaleGetmoneyModel::inView();
        $this ->assign('data',$data['items'][0]);
        $this ->display();
    }
    /*
     * 查询
     * */
    public function Allselect(){
        $roth =I('get.id');
        if($roth ==0){
            $rother = array();
        }else{
            $rother =$roth;
        }

    }
	/**
	 * 结算数据编辑
	 */
	public function returnedit(){
	    if($_POST['price']>$_POST['rprice']){
	        ajax_return(0,'打款金额不能超过申请金额');
            exit;
        }
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




}
