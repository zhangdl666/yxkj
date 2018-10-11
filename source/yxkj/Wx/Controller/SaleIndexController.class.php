<?php
/**
 * Created by PhpStorm.
 * User: hjw
 * Date: 2017/9/23
 * Time: 16:31
 */

namespace Wx\Controller;


class SaleIndexController extends BaseController
{
    protected $model ='SaleIndex';
//    //初始化
//    public function _initialize()
//    {
//        //创建模型
//        $this->model = 'SaleIndex';
//    }

    //首页
    public function index(){
        //当前登录用户  ID
        $uid = $_SESSION['USERINFO']['id'];
        //收益
        $sg=M('saleGetmoney')->where(['sale_id'=>$uid,'status'=>1])->field('id')->select();
        $sgmber=count($sg);
        //认领
        $hotel=M('hotel')->alias('h')
            ->join('yx_hotel_get as g ON g.h_id=h.id')
            ->where(['g.sale_id'=>$uid,'is_default'=>0])
            ->field('sale_id')
            ->select();
        $hmber=count($hotel);
        //合同

        //报销
        $rst=M('reimbursement')->where(['sale_id'=>$uid,'status'=>1])->field('id')->select();
        $rstmber=count($rst);
        //结算
        $rmy=M('return_money')->where(['status'=>1])->field('id')->select();
        $rmymber=count($rmy);

        //酒店
        $hotel=M('hotel')->where(['is_get'=>0])->field('id')->select();
        $hmber=count($hotel);
        $this->assign('hmber',$hmber);
        $this->assign('rstmber',$rstmber);
        $this->assign('rmymber',$rmymber);
        $this->assign('hmber',$hmber);
        $this->assign('sgmber',$sgmber);
        $this->display('index');
    }
}