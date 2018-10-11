<?php
/**
 * Created by PhpStorm.
 * User: hjw
 * Date: 2017/9/27
 * Time: 9:12
 */

namespace Wx\Controller;


class FinanceIndexController extends BaseController
{
    //初始化
    public function _initialize()
    {
        //创建模型
        $this->model = 'return_money';
    }

    public function index(){
        //待结算的数据
        $return_money=M('return_money')->where(['status'=>1])->select();
        $rmnum=count($return_money);
        $this->assign('rmnum',$rmnum);

        //报销
        $reimbursement=M('reimbursement')->where(['status'=>1])->select();
        $rbnum=count($reimbursement);
        $this->assign('rbnum',$rbnum);

        //提现
        $sale_getmoney=M('sale_getmoney')->where(['status'=>1])->select();
        $snum=count($sale_getmoney);
        $this->assign('snum',$snum);
        $this->display();
    }
}