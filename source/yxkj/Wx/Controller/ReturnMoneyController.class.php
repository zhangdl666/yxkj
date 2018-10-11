<?php
/**
 * Created by ${CONTROLLER_NAME}.
 * @auther: 刘小伟
 * Date: 2017/9/21
 * Time: 15:28
 */
namespace Wx\Controller;
use Wx\Model\HotelGetModel;
use Wx\Model\ReturnMoneyModel;

class ReturnMoneyController extends BaseController{
    /**
     * 列表数据
     */
    public function index()
    {
        $roth =I('get.type', 1, 'intval');
        if(is_numeric($roth)){
            $where['returnc.status'] = $roth;
        }else{
            $where =null;
        }
        switch($role = $_SESSION['USERINFO']){
            case($role['role_id'] ==  2):
                $arr = M('HotelGet') ->field('h_id') ->where(array('sale_id' =>$role['id'],'is_default' =>1)) ->select() ;
                foreach ($arr as $k =>$v){
                    $array[] = $v['h_id'];
                }
                if(!empty($array)) {
                    $where['returnc.h_id'] = array('in',$array);
                }else{
                    $where['returnc.h_id'] = 0 ;
                }
                break;
            case($role['role_id'] ==  10):
                $where['returnc.h_id'] =$_SESSION['USERINFO']['hotel_id'];
                $flagWhere=array('h_id'=>$_SESSION['USERINFO']['hotel_id'],'status'=>1);
                $flagNum=D('ReturnMoney')->where($flagWhere)->count();
                if($flagNum != 0){
                    $this->assign('flag',1);
                }
                break;
            case($role['role_id'] ==  3):
                $xiaoshou = M('User') ->where(array('parent_id'=>$role['id']))->field('id') ->select();
                foreach ($xiaoshou as $key =>$val){
                    $arr = M('HotelGet') ->field('h_id') ->where(array('sale_id' =>$val['id'],'is_default' =>1)) ->select() ;
                    foreach ($arr as $k =>$v){
                        $array[] = $v['h_id'];
                    }
                }
                if(is_array($array)) {
                    $where['returnc.h_id'] = array('in',$array);
                }else{
                    $where['returnc.h_id'] = 0;
                }
                break;
        }

        if($role['role_id'] == 6){
            $flagWhere=array('status'=>2);
            $flagNum = D('ReturnMoney')->where($flagWhere)->count();
            if($flagNum != 0){
                $this->assign('flagTwo',1);
            }
        }
        $data = 'ReturnHotelHotelaHotelcView';
        $data_list =D('ReturnMoney')->getList($data,$where,$order_str='returnc.mtime desc,returnc.time desc');
        foreach ($data_list['datas'] as &$val){
            $img = explode(',',$val['hotel_img']);
            if(!empty($img[0]) && !file_exists($_SERVER['DOCUMENT_ROOT'].$img[0])){
                $img[0] = '';
            }
            $val['hotel_img'] = $img[0];
        }
        //将当前列表的url保存到cookie中,为了 删除,添加,修改状态,编辑 之后跳转到该地址
        cookie('__forwards__', $_SERVER['REQUEST_URI']);
        $this->assign('items',$data_list['datas']);
        $this->assign('type',$roth);
        $this->display();
    }
    public function edit()
    {
       $this ->_before_edit_view();
        $this ->display();
    }

    /*
     * 待结算数据
     * */
    public function _before_edit_view(){
        $type =I('get.type');
        $data =ReturnMoneyModel::inView(I('get.id'));
        //上传图片所需
        $appid = C('WEIXIN.AppID');
        $appSecret = C('WEIXIN.AppSecret');
        $jssdk = new JSSDK($appid,$appSecret);
        $sign_package = $jssdk->getSignPackage();
        $this->assign($sign_package);
        $this ->assign('data',$data);
        $this ->assign('type',$type);
    }

    /*
     * 打款数据编辑
     * */
    public function returnedit(){
         //123
        if(!empty($_POST['give_img']) ){
            $_POST['time'] = time();
            $_POST['status'] = 2;
           if(empty($_POST['price'])){
               ajax_return(0,'请填写回款金额！');
               return false;
           }elseif($_POST['price'] <= 0){
               ajax_return(0,'回款金额只能为正数！');
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
                    //工单号
                    //$rmsno = $this->model->getFieldById(I('post.id'),'sno');
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
     * 到账数据编辑
     */
    public function returnconfim(){
        if(!empty($_POST['Arrival']) ){
            $_POST['get_img']= $_POST['Arrival'];
            unset($_POST['Arrival']);
            unset($_POST['role']);
            $_POST['status'] = 3;
        }else{
            $info = '请上传到账凭证！';
            ajax_return(0,$info);
            return false;
        }
        $where['r.id']=$_POST['id'];
        $where['h.is_default'] = 1;
        $price = M('ReturnMoney')
            ->alias('r')
            ->join(' left join yx_hotel_get as h on h.h_id = r.h_id')
            ->join( ' left join yx_sale_ext as s on h.sale_id = s.u_id')
            ->where($where)
            ->field('r.price,h.sale_id,s.cl_id')
            ->find();
        $_POST['cl_id'] = $price['cl_id'];

        $id = I('post.id');
        $re_status = M('ReturnMoney') ->where(array('id'=>$_POST['id'])) ->save($_POST);
        if($re_status != 0){
            //查找对应渠道百分比计算金额
            $rate = M('SaleExt') ->alias('o')
                ->join('yx_channel_level as c on c.id = o.cl_id')
                ->where(array('o.u_id'=>$price['sale_id']))
                ->field('c.rate,o.price')
                ->find();
            $profit = number_format($price['price'] * $rate['rate']/100,2);
            $profit_arr = explode(',', $profit);
            $profit = 0;
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
                
                ajax_return(1,'操作成功',cookie('__forwards__'));
                return false;
            }else{
                ajax_return(0,'操作失败！');
                return false;
            }
        }
    }
    /*
       * 计算未处理事务
       * */
    public function getNoHaddleNum(){
        $user = $_SESSION['USERINFO'];
        if($user['role_id'] == 2){
            $num =0;
        }elseif($user['role_id'] == 10){
            $where =$_SESSION['USERINFO']['hotel_id'];
            $num = M('ReturnMoney') -> where(array('status' =>1,'h_id'=>$where)) ->count();
        }elseif ($user['role_id'] == 6){
            $num = M('ReturnMoney') -> where(array('status' =>2)) ->count();
        }
        return $num;
    }


}