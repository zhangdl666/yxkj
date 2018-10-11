<?php
/**
 * Created by PhpStorm.
 * User: hjw
 * Date: 2017/9/20
 * Time: 17:29
 */

namespace Wx\Controller;
use Wx\Model\SaleExtModel;
use Wx\Controller\BaseController;

class SaleExtController extends BaseController{
    //展示列表
    public function index(){
        $role_id = $_SESSION['USERINFO']['role_id'];
        $user = $_SESSION['USERINFO'];
        //排序
        $order = I('get.order');
        if($role_id==3){
            //销售经理
            $row=M('user')->alias('u')
                ->join('yx_sale_ext as s ON u.id=s.u_id')
                ->join('yx_channel_level as c ON s.cl_id = c.id')
                ->join('yx_earnings as e ON e.sale_id=s.u_id')
                ->field('u.name as uname,u.id,u.mobile,s.channel_type,c.name,e.price')
                ->where(['u.role_id'=>2])
                ->order("e.price $order")
                ->select();
            //将当前列表的url保存到cookie中,为了 删除,添加,修改状态,编辑 之后跳转到该地址
            cookie('__forwards__', $_SERVER['REQUEST_URI']);
            $this->assign('row', $row);
            $this->display('manager');
        }elseif ($role_id==2){
            //销售人员
            //得到销售人员渠道总收益
            $erows=M('earnings')->where(['sale_id'=>$user['id']])->field('price')->select();
            $eprice=0;
            foreach ($erows as $val){
                $eprice+=$val['price'];
            }
            $saleAllMoney=number_format($eprice,2);
            $this->assign('saleAllMoney',$saleAllMoney);//渠道总收益
            //得到报销总额
            $rrows = M('reimbursement')->where(['sale_id'=>$user['id'],'status'=>['notin','4,5']])->field('price')->select();
            $rprice=0;
            foreach ($rrows as $val){
                $rprice+=$val['price'];
            }
            $saleAllReimbursementMoney=number_format($rprice,2);
            $this->assign('saleAllReimbursementMoney',$saleAllReimbursementMoney);//报销总额
            //已到手的报销总额
            $rrowss=M('reimbursement')->where(['sale_id'=>$user['id'],'status'=>3])->field('price')->select();
            $rprices=0;
            foreach ($rrowss as $val){
                $rprices+=$val['price'];
            }
            //查询出渠道表 和销售人员表的信息
            $user_id = session('USERINFO');
            $crow = M('channel_level')->alias('c')
                ->join('yx_sale_ext as s  ON s.cl_id = c.id ')
                ->where(array('u_id' => $user_id['id']))
                ->find();
            $saleAllGetMoney = number_format($crow['all_price'],2);
            $this->assign('saleAllGetMoney',$saleAllGetMoney);//提现总额
            if($rprice>=$crow['price']){
                $saleLeftMoney = '0.00';
            }else{
                $saleLeftMoney = number_format($crow['price'] - $rprice,2);
            }
            $this->assign('saleLeftMoney',$saleLeftMoney);//可提现金额
            $levelData=M('channel_level')->order('room_num')->select();
            $next=array();
            $num=count($levelData);
            foreach ($levelData as $key=>$val){
                if($val['id'] == $crow['cl_id'] && $key<$num-1){
                    $next['name'] = $levelData[$key+1]['name'];
                    $next['rate'] = $levelData[$key+1]['rate'];
                    $next['room_less'] = $levelData[$key+1]['room_num'] - $crow['room_num'];
                }
            }
            //已回款金额总数
            $returnMoney = M('returnMoney')->alias('r')
                ->join(' LEFT JOIN '.C('DB_PREFIX').'hotel_contract as hc on hc.id = r.hc_id')
                ->join(' LEFT JOIN '.C('DB_PREFIX').'hotel_get as hg on hg.h_id = hc.h_id')
                ->join(' LEFT JOIN '.C('DB_PREFIX').'channel_level as c on r.cl_id = c.id')
                ->group('r.cl_id')
                ->field('sum(r.price) as price,c.rate,c.name,hg.sale_id,r.id')
                ->where(['r.status' => 3,'hg.sale_id'=>$user_id['id'],'hg.is_default'=>1,'r.rprice'=>array('neq',0)])->select();
            if($returnMoney){
                //已回款提成收益
                $count=count($returnMoney);
                foreach ($returnMoney  as $key=>&$val){
                    $val['trueprofit'] = round($val['price'] * $val['rate'] / 100,2);
                }
            }else{
                $returnMoney[0]=array(
                    'price' => 0.00,
                    'rate' => $crow['rate'],
                    'name' =>$crow['name'],
                    'trueprofit' => 0.00
                );

            }
            //待回款金额总数
            $dmoney = 0;
            $returnM = M('returnMoney')
                ->alias('r')
                ->join(' LEFT JOIN '.C('DB_PREFIX').'hotel_contract as hc on hc.id = r.hc_id')
                ->join(' LEFT JOIN '.C('DB_PREFIX').'hotel_get as hg on hg.h_id = hc.h_id')
                ->where(['r.status'=>['neq',3],'hg.sale_id'=>$user['id'],'hg.is_default'=>1])
                ->field('r.rprice')
                ->select();
            foreach ($returnM as $rem) {
                $dmoney += $rem['rprice'];
            }
            //待汇款提成收益
            $dzprofit = round($dmoney * $crow['rate'] / 100,2);
            if(!empty($next)){
                $next['money_more'] = $next['rate']-$crow['rate'];
                $next['trueprofit'] = round($dmoney*$next['rate']/100,2);
            }
            //将当前列表的url保存到cookie中,为了 删除,添加,修改状态,编辑 之后跳转到该地址
            cookie('__forwards__', $_SERVER['REQUEST_URI']);
            $this->assign('next',$next);
            $this->assign('dmoney', $dmoney);
            $this->assign('dzprofit', $dzprofit);
            $this->assign('count',count($returnMoney));
            $this->assign('zprofit',$returnMoney);
            $this->assign('crow', $crow);

            $this->display('personnel');
        }elseif ($role_id==9){
            //平台总经理
            $row=M('user')->alias('u')
                ->join('yx_sale_ext as s ON u.id=s.u_id')
                ->join('yx_channel_level as c ON s.cl_id = c.id')
                ->join('yx_role as r ON r.id = u.role_id')
                ->join('yx_earnings as e ON e.sale_id=s.u_id')
                ->field('u.name as uname,u.id,u.mobile,s.channel_type,c.name,e.price')
                ->where(['r.type'=>1])
                ->order("e.price $order")
                ->select();
            //var_dump($row);exit;
            //将当前列表的url保存到cookie中,为了 删除,添加,修改状态,编辑 之后跳转到该地址
            cookie('__forwards__', $_SERVER['REQUEST_URI']);
            $this->assign('row', $row);
            $this->assign('order', $order);
            $this->display('manager');
        }
    }

    //提现记录
    public function record(){
       $user = session('USERINFO');
       $status=I('get.status');
        //下拉加载数据
        if($status == 1){
            $where['saleGetmoney.status'] = 1;
            $where['saleGetmoney.sale_id'] = $user['id'];
        }elseif ($status ==2) {
            $where['saleGetmoney.status'] = 2 ;
            $where['saleGetmoney.sale_id'] = $user['id'];
        }
        else{
            $where['saleGetmoney.sale_id'] = $user['id'];
        }
        $model = 'HotelcSaleGetmoneyView';
        $row = D('saleGetmoney')->getList($model,$where,$order_str='ctime desc');
        $this->assign('items', $row['datas']);
        $this->assign('status', $status);
        $this->assign('uname', $user['real_name']);
        $this->display('record');

    }
    /**
     * 加载更多数据
     */
    public function show_more_data($key, $num = 10){
        $get_datas = D("saleGetmoney")->showMoreDatas($key,$num);
        if ($get_datas) {
            $this->get_ajax_return(1,'加载成功！',$get_datas);
        } else {
            $this->get_ajax_return(0,'加载失败！');
        }
    }

    //查看待付款 和已付款
    public function recordstt()
    {
        $id = I('get.id');
        $row = M('SaleGetmoney')->where(['id' => $id])->find();
        if($row['give_img']){
            $row['give_img'] = explode(',', $row['give_img']);
        }
        $row1 = M('account')->alias('a')
            ->join('yx_bank_info as b  ON b.id=a.bank_type ')
            ->field('a.* , b.name as bname')
            ->where(array('a.id' => $row['account_id']))
            ->find();
        if ($row['status'] == 1) {
            $this->assign('row', $row);
            $this->assign('row1', $row1);
            $this->display('recordstt');
        } else {
            $this->assign('row', $row);
            $this->assign('row1', $row1);
            $this->display('recordstt2');
        }
    }

    //跳转到添加银行卡页面
    public function jumpbank(){
        $row=M('bank_info')->select();
        $this->assign('row', $row);
        $this->display('jumpbank');
    }

    //添加银行卡信息
    public function addbank(){

        if (!D('Account')->create()) {
            // 如果创建失败 表示验证没有通过 输出错误提示信息
            ajax_return(0, D('Account')->getError());
        } else {
            $user_id = session('USERINFO');
            $model = M('account');
            $model->sale_id = $user_id['id'];
            $model->name = $_POST['name'];
            $model->bank_num = $_POST['bank_num'];
            $model->bank_name = $_POST['bank_name'];
            $model->bank_type = $_POST['bank_type'];
            $num = $model->add();
            if (is_numeric($num)) {
                ajax_return(1, "添加成功!", U('apply'));
            } else {
                $model = "添加失败";
                ajax_return(0, $model);
                return true;
            }
        }
    }

    //到申请提现页面
    public function apply(){
        $id = session('USERINFO');
        $row = M('account')->alias('a')
            ->join('yx_bank_info as b  ON b.id=a.bank_type ')
            ->field('a.* , b.name as bname')
            ->where(array('a.sale_id' => $id['id']))
            ->select();
        $row1 = M('saleExt')->where(array('u_id' => $id['id']))->field('price')->find();
        //dump($row1);exit;
        $saleReimursement=M('Reimbursement')->where(['sale_id'=>$id['id'],'status'=>array('in',array(1,2,3))])->field('price')->select();
        $rAll=0;
        foreach ($saleReimursement as $val){
            $rAll+=$val['price'];
        }
        if($rAll>=$row1['price']){
            $row1['price'] = 0;
        }else{
            $row1['price'] = $row1['price'] - $rAll;
        }
        session('Cash',time());
        $this->assign('Cash',session('Cash'));
        $this->assign('row', $row);
        $this->assign('row1', $row1);
        $this->display('apply');
    }

    //添加提现
    public function addapply(){
        $post=I('post.');
        if($post['Cash'] != session('Cash')){
            $result['info'] = '不能重复提交';
            $this->ajaxReturn($result);
        }
        $userInfo=session('USERINFO');
        $result=array('status'=>0,'info'=>'提现失败');
        $date=date('d',time());
        //先判断提现时间
//        if($date>10){
//            $result['info'] = '只能每月1~10号进行提现';
//            $this->ajaxReturn($result);
//        }
//        //判断本月是否有提现记录
//        $information=M('SaleGetmoney')->where(array('sale_id'=>$userInfo['id']))->order('ctime desc')->getField('ctime');
//       if(date('Y-m',$information) == date('Y-m',time())){
//           $result['info'] = '每月只能提现一次';
//           $this->ajaxReturn($result);
//       }
        $sModel=D('sale_getmoney');
        $sModel->startTrans();
        if(!$sModel->create()){
            $sModel->rollback();
            $result['info'] = $sModel->getError();
            $this->ajaxReturn($result);
        }
        if(empty($post['shang_name'])){
            $sModel->rollback();
            $result['info'] = '请填写提现卡号';
            $this->ajaxReturn($result);
        }
        //个人所得税
        $mprice = ($post['rprice'] - 100) > 0 ? $post['rprice'] * 0.01 : 0;
        //到账金额
        $price = $post['rprice'] - $mprice;
        //获取用户现在的账户的可提现金额和已提现金额
        $nowSe= M('saleExt')->where(array('u_id' => $userInfo['id']))->find();
        $saveData=array(
            'price'=>$nowSe['price'] - $post['rprice'],
            'all_price'=>$nowSe['all_price'] + $post['rprice'],
        );
        $saleExtRes=M('saleExt')->where(array('u_id' => $userInfo['id']))->save($saveData);
        if(!$saleExtRes){
            $sModel->rollback();
            $this->ajaxReturn($result);
        }
        $addData=array(
            'sale_id'=>$userInfo['id'],
            'sno'=>get_reimbursement_sn(),
            'rprice'=>$post['rprice'],
            'price'=>$price,
            'mprice'=>$mprice,
            'ctime' => time(),
            'account_id'=>''
        );
        $addData['account_id'] = M('account')->where(array('bank_num'=>$post['shang_name']))->getField('id');
        $addRes=$sModel->add($addData);
        if(!$addRes){
            $sModel->rollback();
            $this->ajaxReturn($result);
        }else{
            $sModel->commit();
            $user_idarr = M('User')->field('id')->where(array('role_id'=>6,'status'=>1))->select();
            if($user_idarr){
                $oper_title  = '待处理提现工单';
                $msg_content = '您有一条提现工单号：'.$addData['sno'].' 待处理';
                $user_ids = implode(',', i_array_column($user_idarr,'id'));
                $oper_url = 'SaleGetmoney/index';
                has_oper($msg_content,$user_ids,$oper_url,$oper_title);
            }
            $result['status'] = 1;
            $result['info'] = '提现成功';
            $this->ajaxReturn($result);
        }

    }
}