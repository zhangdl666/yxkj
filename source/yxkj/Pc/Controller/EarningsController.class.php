<?php
/**
 * Created by PhpStorm.
 * User: hjw
 * Date: 2017/9/13
 * Time: 10:20
 */

namespace Pc\Controller;

use Pc\Server\PageModel;
class EarningsController extends BaseController
{
    //查询销售经理收益情况
    public function index(){
        $filter = I('get.');
        $where = array();
        $where['u.status'] = 1;
        if(!empty($filter['keyword'])){
            $where['u.real_name'] = array('like','%'.$filter['keyword'].'%');
        }
        if(!empty($filter['order'])){
            $orderBy = 'sprice '.$filter['order'];
        }else{
            $orderBy = 'sprice desc';
        }
        $userInfo = session('USERINFO');
        if($userInfo['role_id'] == 3){//销售经理只能查看自己手下的销售人员的情况
            $saleIdData = M('user')->where(array('parent_id'=>$userInfo['id'],'status'=>1))->field('id')->select();
            $saleIds = i_array_column($saleIdData,'id');
            $where['u.id'] = array('in',$saleIds);
        }
        $count=M('user')->alias('u')
            ->join('yx_earnings as e on u.id = e.sale_id')
            ->join('yx_sale_ext as s on u.id = s.u_id')
            ->join('yx_channel_level as c on s.cl_id = c.id')
            ->where($where)
            ->field('u.real_name as uname,u.id,u.mobile,s.channel_type,c.name,sum(e.price) as sprice')
            ->count('distinct u.id');
        $page = new PageModel($count,10);
        $first = $page->firstRow;
        $listRow = $page->listRows;
        $row = M('user')->alias('u')
            ->join('yx_earnings as e on u.id = e.sale_id')
            ->join('yx_sale_ext as s on u.id = s.u_id')
            ->join('yx_channel_level as c on s.cl_id = c.id')
            ->group('u.id')
            ->where($where)
            ->field('u.real_name as uname,u.id,u.mobile,s.channel_type,c.name,sum(e.price) as sprice')
            ->order($orderBy)
            ->limit($first,$listRow)
            ->select();
        //统计获取收益的人数
        $pn = count($row);
        //统计渠道收益总额
        $ca = 0;
        foreach ($row as $val){
            $ca +=$val['sprice'];
        }
        //统计每人平均收益
        $ag = number_format(($ca / $pn),2);
        $this->assign('pn', $pn);
        $this->assign('ca', $ca);
        $this->assign('ag', $ag);
        $this->assign('row', $row);
        $this->assign('pageHtml',$page->show());
        $this->assign('filter',$filter);
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display();
    }
    public function indexOld()
    {
        $rows = M('Earnings')->select();
        //统计人数
        //渠道之和
        $ca = 0;
        $data = M('Earnings')
            ->group('sale_id')
            ->field('sum(price)')
            ->select();
        $pn = count($data);
        //var_Dump($pn);exit;
        foreach ($rows as $key => $row) {

            $ca += $row['price'];
        }
        //每人平均收益
        $ag = $ca / $pn;
    //搜索和排序
        //关键字
        $keyword = I('get.keyword');
        $order = I('get.order');
        $wheres['u.name']= array('like','%'.$keyword.'%');
        //dump($wheres);exit;
        if ($keyword!=null || $order!=null) {
            $row1[] = M('channel_level')->alias('c')
                ->join('yx_sale_ext as s  ON s.cl_id = c.id ')
                ->join('yx_user as u ON u.id=s.u_id')
                ->join('yx_earnings as e ON e.sale_id=s.u_id')
                ->group('e.sale_id')
                ->field('u.real_name as uname,u.id,u.mobile,s.channel_type,c.name,sum(e.price) as sprice')
                ->where($wheres)
                ->order("sprice $order")
                ->select();
        }else{
            $row1[] = M('channel_level')->alias('c')
                ->join('yx_sale_ext as s  ON s.cl_id = c.id ')
                ->join('yx_user as u ON u.id=s.u_id')
                ->join('yx_earnings as e ON e.sale_id=s.u_id')
                ->group('e.sale_id')
                ->field('u.real_name as uname,u.id,u.mobile,s.channel_type,c.name,sum(e.price) as sprice')
                ->select();
        }
        //将当前列表的url保存到cookie中,为了 删除,添加,修改状态,编辑 之后跳转到该地址
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->assign('pn', $pn);
        $this->assign('ca', $ca);
        $this->assign('ag', number_format($ag,2));
        $this->assign('row1', $row1);
        //dump($row1);exit;
        $this->display('index');
    }


}