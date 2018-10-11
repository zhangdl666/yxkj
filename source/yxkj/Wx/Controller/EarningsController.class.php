<?php
/**
 * Created by PhpStorm.
 * User: hjw
 * Date: 2017/9/13
 * Time: 10:20
 */

namespace Wx\Controller;


class EarningsController extends BaseController
{
    //展示列表
    public function index(){
        //排序
        $order = I('get.order');
        $where = array();
        //获取该销售经理下的所有销售人员
        $userInfo = session('USERINFO');
        if($userInfo['role_id'] == 3){
            $saleIdData=M('user')->where(array('parent_id'=>$userInfo['id'],'status'=>1))->field('id')->select();
            $saleIds = i_array_column($saleIdData,"id");
            $where['u.id'] = array('in',$saleIds);
        }else{
            $where['u.role_id'] = 2;
        }



        //销售经理
        $row=M('user')->alias('u')
            ->join('yx_sale_ext as s ON u.id=s.u_id')
            ->join('yx_channel_level as c ON s.cl_id = c.id')
            ->join('yx_earnings as e ON e.sale_id=s.u_id')
            ->group('e.sale_id')
            ->field('u.real_name as uname,u.id,u.mobile,s.channel_type,c.name,sum(e.price) as sprice')
            ->where($where)
            ->order("sprice $order")
            ->select();
        //将当前列表的url保存到cookie中,为了 删除,添加,修改状态,编辑 之后跳转到该地址
        cookie('__forwards__', $_SERVER['REQUEST_URI']);
        $this->assign('row', $row);
        $this->assign('order', $order);
        $this->display('manager');
    }

}