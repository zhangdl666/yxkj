<?php
/**
 * HotelContractController.class.php
 * 结算
 * @author 刘小伟
 * @date   2017-09-11 18:02
 */
namespace Pc\Controller;
use Pc\Model\ReturnMoneyModel;

class ReturnMoneyController extends BaseController{
	/**
	 * 列表数据
	 */
    public function index()
    {
        $p = I('get.p', 1, 'intval');
        $roth =I('get.id', 0, 'intval');
        if($roth ==0){
            $rother = array();
            $type = '全部';
        }else{
            $rother =$roth;
            if($rother == 1){
                $type = '待结算';
            }elseif($rother == 2){
                $type = '待确认';
            }else{
                $type = '已确认';
            }
        }
        $role = $_SESSION['USERINFO']['role_id'];
        $count =D('ReturnMoney')->counter();
        $data =D('ReturnMoney')->getResultLister($rother,$p);
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->assign('items', $data['items']);
        $this->assign('count', $count);
        $this->assign('type', $type);
        $this->assign('role', $role);
        $this->assign('pageHtml', $data['pageHtml']);
        $this->display();
    }

    public function _before_edit_view()
    {
        $id =I('id');
        $data =ReturnMoneyModel::inView($id);
        $this ->assign('data',$data);
    }
    /*
   * 结算之前准备数据
   * */
    public function settlement(){
        $this ->_before_edit_view();
        $this ->display();
    }
    /*
         * 到账确认之前准备数据
         * */
    public function confirmation(){
        $this ->_before_edit_view();
        $this ->display();
    }
    /*
         * 查看之前准备数据
         * */
    public function looked(){
        $this ->_before_edit_view();
        $this ->display();
    }

	/**
	 * 结算数据编辑
     *
	 */
	public function returnedit(){
        if($_POST['dakuan']){
            $_POST['give_img']=implode(",",$_POST['dakuan']);
            $_POST['time'] = time();
            $_POST['status'] = 2;
            unset($_POST['dakuan']);
            if($_POST['price'] == 0){
                ajax_return(0,'到账金额为只能为正数！');
                return false;
            }

            //是否已经回款
            $rm_price = $this->model->where(array('id'=>$_POST['id']))->getField('price');
            if($rm_price == 0){
                //操作消息消失
                $rmsno = $this->model->getFieldById(I('post.id'),'sno');
                not_oper($rmsno,session('USERINFO.id'));
                /* 平台财务经理做处理 */
                //平台财务经理
                $user_idarr = M('User')->field('id')->where(array('role_id'=>6,'status'=>1))->select();
                if($user_idarr){
                    $oper_title  = '待处理结算单';
                    $msg_content = '您有一条结算工单号：'.$rmsno.' 待处理';
                    $user_ids = implode(',', i_array_column($user_idarr,'id'));
                    $oper_url = 'ReturnMoney/index';
                    has_oper($msg_content,$user_ids,$oper_url,$oper_title);
                }

                //平台总经理 收到打款消息
                $user_idarr = M('User')->field('id')->where(array('role_id'=>9,'status'=>1))->select();
                if($user_idarr){
                    $user_ids = implode(',', i_array_column($user_idarr,'id'));
                    $msg_content = '结算工单号：'.$rmsno.' 已回款:'.$_POST['price'].'元';
                    has_oper($msg_content,$user_ids,'','打款通知','系统消息');
                }
            }            
        }else{
            $info = '请上传打款凭证！';
            ajax_return(0,$info);
            return false;
        }
      $this->operation();
	}
    /**
     * 确认数据编辑
     */
    public function returnconfim(){
        if(!empty($_POST['img'])){
            $id = I('post.id');
            $_POST['get_img']=implode(",",$_POST['img']);
            unset($_POST['img']);
            unset($_POST['role']);
            $_POST['status'] = 3;
            $where['r.id']=$_POST['id'];
            $where['h.is_default'] = 1;
            $price = M('ReturnMoney')
                ->alias('r')
                ->join('yx_hotel_get as h on h.h_id = r.h_id')
                ->join(C('DB_PREFIX').'sale_ext as s on h.sale_id = s.u_id')
                ->where($where)
                ->field('r.price,h.sale_id,s.cl_id')
                ->find();
            $_POST['cl_id'] = $price['cl_id'];
            $re_status = M('ReturnMoney') ->where(array('id'=>$_POST['id'])) ->save($_POST);
            if($re_status != 0){
            //查找对应渠道百分比计算金额
            $rate = M('SaleExt') ->alias('o')
                ->join('yx_channel_level as c on c.id = o.cl_id')
                ->where(array('o.u_id'=>$price['sale_id']))
                ->field('c.rate,o.price')
                ->find();
            $profit = number_format($price['price'] * $rate['rate'] / 100,2);
            $profit_arr = explode(',', $profit);
            $profit = '';
            for ($i=0; $i < count($profit_arr); $i++) { 
                $profit .= $profit_arr[$i];
            }
            $money = $profit*1 + $rate['price'];
            M('SaleExt') ->where(array('u_id'=>$price['sale_id'])) ->setField('price',$money);
            $data['price'] =$profit;
            $data['sale_id'] =  $price['sale_id'];
            $data['ctime'] = time();
            $num =  M('Earnings') -> add($data);
            if(is_numeric($num)){
                //操作消息消失
                $sno = $this->model->getFieldById($id,'sno');
                not_oper($sno,session('USERINFO.id'));

                ajax_return(1,'操作成功',cookie('__forward__'));
                return false;
            }else{
                ajax_return(0,'操作失败！');
                return false;
            }
        }
        }else{
            ajax_return(0,'请上传到账凭证！');
            return false;
        }
    }


}
