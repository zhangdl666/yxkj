<?php
/**
 * Created by PhpStorm.
 * User: hjw
 * Date: 2017/9/11
 * Time: 10:09
 */

namespace Pc\Controller;
use Pc\Model\SalesModel;
use Pc\Server\PageModel;
class SaleExtController extends BaseController
{
//收益统计信息
    public function index()
    {
        $user_id = session('USERINFO');
        //得到销售人员渠道总收益
        $erows=M('earnings')->where(['sale_id'=>$user_id['id']])->field('price')->select();
        $eprice=0;
        foreach ($erows as $val){
            $eprice+=$val['price'];
        }
        $saleAllMoney=number_format($eprice,2);
        $this->assign('saleAllMoney',$saleAllMoney);//渠道总收益

        //得到报销总额
        $rrows = M('reimbursement')->where(['sale_id'=>$user_id['id'],'status'=>['notin','4,5']])->field('price')->select();
        $rprice=0;
        foreach ($rrows as $val){
            $rprice+=$val['price'];
        }
        $saleAllReimbursementMoney=number_format($rprice,2);
        $this->assign('saleAllReimbursementMoney',$saleAllReimbursementMoney);//报销总额

        //查询出渠道表 和销售人员表的信息
        $row = M('channel_level')->alias('c')
            ->join('yx_sale_ext as s  ON s.cl_id = c.id ')
            ->where(array('u_id' => $user_id['id']))
            ->find();
        $saleAllGetMoney = number_format($row['all_price'],2);
        $this->assign('saleAllGetMoney',$saleAllGetMoney);//提现总额
        if($rprice>=$row['price']){
            $saleLeftMoney = '0.00';
        }else{
            $saleLeftMoney = number_format($row['price'] - $rprice,2);
        }
        $this->assign('saleLeftMoney',$saleLeftMoney);//可提现金额
        $levelData=M('channel_level')->order('room_num')->select();
        $next=array();
        $num=count($levelData);
        foreach ($levelData as $key=>$val){
            if($val['id'] == $row['cl_id'] && $key<$num-1){
                $next['name'] = $levelData[$key+1]['name'];
                $next['rate'] = $levelData[$key+1]['rate'];
                $next['room_less'] = $levelData[$key+1]['room_num'] - $row['room_num'];
            }
        }
        //已回款金额总数
        //已回款金额总数
        $returnMoney = M('returnMoney')->alias('r')
            ->join(' LEFT JOIN '.C('DB_PREFIX').'hotel_contract as hc on hc.id = r.hc_id')
            ->join(' LEFT JOIN '.C('DB_PREFIX').'hotel_get as hg on hg.h_id = hc.h_id')
            ->join(' LEFT JOIN '.C('DB_PREFIX').'channel_level as c on r.cl_id = c.id')
            ->group('r.cl_id')
            ->field('sum(r.price) as price,c.rate,c.name')
            ->where(['r.status' => 3,'hg.sale_id'=>$user_id['id'],'hg.is_default'=>1,'r.rprice'=>array('neq',0)])
            ->select();
        if($returnMoney){
            $count=count($returnMoney);
            //已回款提成收益
            foreach ($returnMoney as $key=>&$val){
                $val['trueprofit'] = number_format($val['price'] * $val['rate'] / 100,2);
            }

        }else{
            $returnMoney[0]=array(
                'price' => 0.00,
                'rate' => $row['rate'],
                'name' =>$row['name'],
                'trueprofit' => 0.00
            );

        }
        //待回款金额总数
        $dmoney = 0;
        $returnM = M('returnMoney')
            ->alias('r')
            ->join(' LEFT JOIN '.C('DB_PREFIX').'hotel_contract as hc on hc.id = r.hc_id')
            ->join(' LEFT JOIN '.C('DB_PREFIX').'hotel_get as hg on hg.h_id = hc.h_id')
            ->where(['r.status'=>['in',[1,2]],'hg.sale_id'=>$user_id['id'],'hg.is_default'=>1])
            ->field('r.rprice')
            ->select();
        foreach ($returnM as $rem) {
            $dmoney += $rem['rprice'];
        }
        //提成收益
        $dzprofit = $dmoney * $row['rate'] / 100;
        $dzprofit=number_format($dzprofit,2);
        if(!empty($next)){
            $next['money_more'] = $next['rate'] - $row['rate'];
            $next['trueprofit'] = round($dmoney*$next['rate']/100,2);
            //$next['money_more'] = 0;
        }
        //将当前列表的url保存到cookie中,为了 删除,添加,修改状态,编辑 之后跳转到该地址
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        $this->assign('next',$next);
        $this->assign('dmoney', $dmoney);
        $this->assign('dzprofit', $dzprofit);
        $this->assign('count',count($returnMoney));
        $this->assign('zprofit', $returnMoney);
        $this->assign('row', $row);
        $this->display('index');
    }

//跳转到申请提现页面
    public function add()
    {
        //将当前列表的url保存到cookie中,为了 删除,添加,修改状态,编辑 之后跳转到该地址
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        $id = session('USERINFO');
        $row = M('account')->alias('a')
            ->join('yx_bank_info as b  ON b.id=a.bank_type ')
            ->field('a.* , b.name as bname')
            ->where(array('a.sale_id' => $id['id']))
            ->select();
        $row1 = M('saleExt')->where(array('u_id' => $id['id']))->find();
        //dump($row1);exit;
        $saleReimursement=M('Reimbursement')->where(['sale_id'=>$id['id'],'status'=>array('notin','4,5')])->field('price')->select();
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
        $this->display('add');
    }

//跳转到添加卡号页面
    public function selaccount()
    {
        $row = M('bank_info')->select();
        $this->assign('row', $row);
        $this->display('account');
    }

//添加银行卡信息
    public function addaccount()
    {
        $model = D('account');
        if (!$model->create()) {
            // 如果创建失败 表示验证没有通过 输出错误提示信息
            ajax_return(0, $model->getError());
        } else {
            $user_id = session('USERINFO');
            $data['sale_id'] = $user_id['id'];
            $data['name'] = $_POST['name'];
            $data['bank_num'] = $_POST['bank_num'];
            $data['bank_name'] = $_POST['bank_name'];
            $data['bank_type'] = $_POST['bank_type'];
            $data['ctime'] = time();
            $model->add($data);
            //$this->operation();
            $this->admin_ajax_return(1);
        }

    }

//添加销售人员提现情况
    public function addgetmoney()
    {
        if(session('Cash') != $_POST['Cash']){
            ajax_return(0,'请不要重复提交');
            return false;
        }
        //获取时间，除1~10号以外其它时间不能提现
       $intime =  date('d', time());
//       if(10 < $intime ){
//           ajax_return(0,'只能每月1~10号进行提现');
//           return false;
//       }
       if(empty($_POST['account_id'])){
           ajax_return(0,'请选择提现账号');
           return false;
       }
        $userid = $_SESSION['USERINFO']['id'];
        $information = M('SaleGetmoney') -> where(array('sale_id'=>$userid)) ->field('ctime') ->order('ctime desc') ->find();
    //获取月份进行比较，判断当前月份是否已申请
        $beforetime =  date('Y-m', $information['ctime']);
        $intime =  date('Y-m', time());

//       if ($beforetime == $intime) {
//           ajax_return(0,'每月只能提现一次');
//           return false;
//       }
        //判断提现金额是否超过可提现金额
        if ($_POST['rprice'] <= $_POST['rprices']) {
            $model = D('sale_getmoney');
            if($_POST['rprice']<=0){
                ajax_return(0, '提现金额不能小于或者等于0');
                return false;
            }
            //个人所得税
            $mprice = ($_POST['rprice'] - 100) > 0 ? $_POST['rprice'] * 0.01 : 0;
            //到账金额
           // $price = $_POST['rprice'] - $mprice;
            $price = $_POST['rprice'];
            $id = session('USERINFO');
            M('')->startTrans();
            $row2 = D('saleExt')->where(array('u_id' => $id['id']))->find();
            $row1 = D('saleExt')->where(array('u_id' => $id['id']));
            $row1->all_price = $row2['all_price'] + $_POST['rprice'];
            $row1->price = $row2['price'] - $_POST['rprice'];
            $res=$row1->save();
            if(!$res){
                M('')->rollback();
                ajax_return(0, '提现失败');
                return false;
            }else{
                $model->sale_id = $id['id'];
                $model->rprice = $_POST['rprice'];
                $sno = get_reimbursement_sn();
                $model->sno = $sno;
                $model->mprice = $mprice;
                $model->price = $price;
                $model->account_id = $_POST['account_id'];
                $model->ctime = time();
                $model->Status = 1;
                $num = $model->add();
                if ($num) {
                    M('')->commit();
                    session('Cash',time());
                    $user_idarr = M('User')->field('id')->where(array('role_id'=>6))->select();
                    if($user_idarr){
                        $oper_title  = '待处理提现工单';
                        $msg_content = '您有一条提现工单号：'.$sno.' 待处理';
                        $user_ids = implode(',', i_array_column($user_idarr,'id'));
                        $oper_url = 'SaleGetmoney/index';
                        has_oper($msg_content,$user_ids,$oper_url,$oper_title);
                    }

                    $model = "提现成功";
                    ajax_return(1, $model, U('saleGetmoney'));
                    return true;
                }else{
                    M('')->rollback();
                    ajax_return(0, '提现失败');
                    return false;
                }
            }
        } else {
            //如果大于就跳转到添加页面
            $model = "提现金额超出";
            ajax_return(0, $model);
            return false;
        }
    }
    //查询出提现记录显示在页面  搜索
    public function saleGetmoney()
    {
        $user_id = session('USERINFO');
        //分页
        $p = I('get.p', 1, 'intval');
        $id = I('get.id', 0, 'intval');
        if ($id == 0) {
            $count = M('saleGetmoney')->where(array('sale_id' => $user_id['id']))->count();
        } elseif ($id == 1) {
            $count = M('saleGetmoney')->where(array('status' => 1, 'sale_id' => $user_id['id']))->count();
        } else {
            $count = M('saleGetmoney')->where(array('status' => 2, 'sale_id' => $user_id['id']))->count();;
        }
        $listRows = 20;
        $page = new PageModel($count, $listRows);  //自己获取请求中的页面直接使用
        $page->lastSuffix = false;
        $pageHtml = $page->show();
        if ($id == 0) {
            $rows = M('saleGetmoney')->where(array('sale_id' => $user_id['id']))->page($page->firstRow = $p, $page->listRows)->select();
        } elseif ($id == 1) {
            $rows = M('saleGetmoney')->where(array('status' => 1, 'sale_id' => $user_id['id']))->page($page->firstRow = $p, $page->listRows)->select();
        } else {
            $rows = M('saleGetmoney')->where(array('status' => 2, 'sale_id' => $user_id['id']))->page($page->firstRow = $p, $page->listRows)->select();
        }
        $this->assign('pageHtml', $pageHtml);
        $this->assign('rows', $rows);
        $this->display('getmoney');
    }

    //查看待付款 和已付款
    public function select()
    {
        $id = I('get.id');
        $row = M('saleGetmoney')->where(array('id' => $id))->find();
        $row1 = M('account')->alias('a')
            ->join('yx_bank_info as b  ON b.id=a.bank_type ')
            ->field('a.* , b.name as bname')
            ->where(array('a.id' => $row['account_id']))
            ->find();
        if ($row['status'] == 1) {
            $this->assign('row', $row);
            $this->assign('row1', $row1);
            $this->display('status1');
        } else {
            $this->assign('row', $row);
            $this->assign('row1', $row1);
            $this->display('status2');
        }
    }


    /**
     * 渠道收益统计图加载
     */
    public function earnings()
    {
        $this->display();
    }

    /**
     * 报销统计图加载
     */
    public function reimbursement()
    {
        $this->display();
    }

    /**
     * 渠道收益统计数据
     */
    public function getSaleExt()
    {
        $user = session('USERINFO');
        $rows = M('earnings')->where(array('sale_id' => $user['id']))
            ->field("SUM(price) as price,FROM_UNIXTIME(ctime,'%Y-%m') as months")
            ->group('months')
            ->order('ctime asc')->select();
        $row = arraySequence(SalesModel::toArrayList($rows, SalesModel::getMonth(), 'price'), 'months');
        $this->ajaxReturn($row);
    }


    /**
     * 报销统计数据
     */
    public function getReimbur()
    {
        $user = session('USERINFO');
        $rows = M('reimbursement')->where(array('status' => 3, 'sale_id' => $user['id']))
            ->field("SUM(price) as price,FROM_UNIXTIME(ctime,'%Y-%m') as months")
            ->group('months')
            ->order('ctime asc')->select();
        $row = arraySequence(SalesModel::toArrayList($rows, SalesModel::getMonth(), 'price'), 'months');
        $this->ajaxReturn($row);
    }


}