<?php
/**
 * ReimbursementController.class.php
 * 报销列表
 * @author: wy901216
 * @date: 2017/9/5  11:06
 */

namespace Pc\Controller;
use Pc\Server\NewMessage;
use Pc\Server\PageModel;

class ReimbursementController extends BaseController{
    private $listDataRows;

    public function _initialize(){
        //$this->listDataRows = 7;
        parent::_initialize();
        $this->assign('now_module','Reimbursement');
    }

    /*
     * 报销列表       1待审核 2待打款 3已打款 4已驳回 5已撤回
     * 这个列表涉及四种角色的用户   平台总经理、平台销售人员、平台销售经理、平台财务经理
     * 平台总经理对应的数据是全部待审核、待打款、已打款、已驳回的报销数据     只有查看的权限   (暂时不管此角色)
     * 平台销售经理对应的数据是全部待审核、待打款、已打款、已驳回的的报销数据    有查看、审核(待审核数据)的操作权限
     * 平台销售人员对应的数据是关于自己待审核、待打款、已打款、已驳回、已撤回的报销数据     有查看、添加、编辑(已驳回、已撤回数据)的操作权限
     * 平台财务经理对应的数据是全部待打款、已打款的报销数据     有查看、打款(待打款数据)的操作权限
     ***/
    public function index(){
        $p = I('get.p', 1, 'intval');
        //条件
        $wheres = array();
        $statusdatas = array();
        //获取当前用户角色
        $userinfo = session('USERINFO');
        switch($userinfo['role_id']){
            case 1 :       //超级管理员
                $wheres['obj.status'] = array('in',array(1,2,3,4));
                $statusdatas = array(
                    array('status'=>0,'name'=>'全部'),
                    array('status'=>1,'name'=>'待审核'),
                    array('status'=>2,'name'=>'待打款'),
                    array('status'=>3,'name'=>'已打款'),
                    array('status'=>4,'name'=>'已驳回'),
                );
                break;
            case 2 :             //平台销售人员
                $wheres['obj.sale_id'] = $userinfo['id'];    //报销人员ID
                $statusdatas = array(
                    array('status'=>0,'name'=>'全部'),
                    array('status'=>1,'name'=>'待审核'),
                    array('status'=>2,'name'=>'待打款'),
                    array('status'=>3,'name'=>'已打款'),
                    array('status'=>4,'name'=>'已驳回'),
                    array('status'=>5,'name'=>'已撤回'),
                );
                break;
            case 6 :       //平台财务经理
                $wheres['obj.status'] = array('in',array(2,3));
                $statusdatas = array(
                    array('status'=>0,'name'=>'全部'),
                    array('status'=>2,'name'=>'待打款'),
                    array('status'=>3,'name'=>'已打款'),
                );
                break;
            case 3 :       //平台销售经理
                //获取该平台销售经理下面的平台销售人员
                $users = M("User")->where(array('parent_id'=>$userinfo['id'],'role_id'=>2))->field('id')->select();
                /*if(!empty($users)){
                    $user_ids = array();
                    foreach($users as $user){
                        $user_ids[] = $user['id'];
                    }
                    if(!empty($user_ids)){
                        $wheres['obj.sale_id'] = array('in',$user_ids);    //报销人员ID
                    }

                }*/

                $wheres['obj.status'] = array('in',array(1,2,3,4));
                $statusdatas = array(
                    array('status'=>0,'name'=>'全部'),
                    array('status'=>1,'name'=>'待审核'),
                    array('status'=>2,'name'=>'待打款'),
                    array('status'=>3,'name'=>'已打款'),
                    array('status'=>4,'name'=>'已驳回'),
                );
                break;
            case 9 :       //平台总经理
                $wheres['obj.status'] = array('in',array(1,2,3,4));
                $statusdatas = array(
                    array('status'=>0,'name'=>'全部'),
                    array('status'=>1,'name'=>'待审核'),
                    array('status'=>2,'name'=>'待打款'),
                    array('status'=>3,'name'=>'已打款'),
                    array('status'=>4,'name'=>'已驳回'),
                );
                break;
        }
        $this->assign('statusdatas',$statusdatas);
        $this->assign('role_id',$userinfo['role_id']);

        //计算统计数据
        $statistics = $this->model->getStatisticsData($wheres);
        $this->assign($statistics);

        //关键字
        $keyword = trim(I('get.keyword'));
        if(!empty($keyword)){
            //$wheres['b.name'] = array('like','%'.$keyword.'%');
            $wheres['b.real_name'] = $keyword;
            $this->assign('keyword',$keyword);
        }

        $kstatus = I('get.kstatus');
        //状态
        if($kstatus != '0' && !empty($kstatus)){
            $wheres['obj.status'] = $kstatus;
            $this->assign('kstatus',$kstatus);
            $this->assign('statusname',I('get.statusname'));
        }
        //查询条件
        $this->_set_wheres($wheres);

        //数据查询
        $re_datas = $this->model->getResultList($p,$wheres);
        foreach ($re_datas['items'] as &$val){
            $val['hotel_name'] = msubstr($val['hotel_name'],0,15);
            if($userinfo['role_id'] == 3 && $val['status'] == 1 && !empty($users)){
                foreach ($users as $vo){
                    if($vo['id'] == $val['sale_id']){
                        $val['flag'] = 1;
                        break;
                    }
                }
            }
        }
        $this->assign($re_datas);

        //列表页面展示之前准备数据
        $this->_before_index_view();

        //将当前列表的url保存到cookie中,为了 删除,添加,修改状态,编辑 之后跳转到该地址
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        $this->assign('placeholder',$this->placeholder);

        $this->display();
    }



    //添加
    public function add(){
        $this->_before_edit_view();
        $this->display('add');
    }


    //编辑
    public function edit(){
        $this->_before_edit_view();
        //获取详情
        $id = I('get.id');
        $info = $this->model->getInfo($id);
        $this->assign($info);
        //获取酒店对应的合同
        $hotelContracts = M("HotelContract")->field('id,name')->where(array('h_id'=>$info['h_id']))->select();
        $this->assign('hotelContracts',$hotelContracts);

        $this->display();
    }


    //审核(只有平台销售经理有此权限)
    public function audit(){
        if(IS_GET){
            $id = I("get.id");
            $info = $this->model->getInfo($id);
            $this->assign($info);
            $this->display('audit');
        }else{
            $data = I("post.");
            //获取当前用户id
            $userinfo = session('USERINFO');
            $data['salem_id'] = $userinfo['id'];

            //报销人基本情况
            $row = $this->model->where(array('id'=>$data['id']))->find();
            $total_price = 0;
            $prices = $this->model->field("SUM(price) as price")->where(array('sale_id'=>$row['sale_id'],'status'=>3,'ctime'=>array('elt',time())))->select();
            $total_price+=$prices[0]['price'];
            $data['all_price'] = $total_price;     //累计申请的报销金额

            $claimed_hotels = M("HotelGet")->where(array('sale_id'=>$row['sale_id'],'is_default'=>1))->select();
            $data['hotel_num'] = count($claimed_hotels);    //已认领的酒店数

            $sign_contract_num = 0;
            $payment_price = 0;
            $hotel_ids = array_map(function($v){return $v['h_id'];},$claimed_hotels);
            if(!empty($hotel_ids)){
                $sign_contracts = M("HotelContract")->field('id')->where(array('h_id'=>array('in',$hotel_ids)))->select();
                $sign_contract_num+= count($sign_contracts);

                $hc_ids = array_map(function($v){return $v['id'];},$sign_contracts);
                if(!empty($hc_ids)){
                    $payments = M("ReturnMoney")->field("SUM(price) as price")->where(array('hc_id'=>array('in',$hc_ids),'status'=>3))->select();
                    $payment_price+=$payments[0]['price'];
                }
            }

            $data['hc_num'] = $sign_contract_num;     //已签订的合同数
            $data['rm_price'] = $payment_price;        //合同已回款金额
            $data['now_time'] = time();       //基本信息提取时间
            $re_status = $this->model->save($data);
            if($re_status){
               if($data['status'] == 4){
                   //NewMessage::remind(10,$row['sale_id']);
                    //提醒
                   $contentRow = M('Reimbursement')->where(array('id'=>$data['id']))->find();
                   $time = date('Y-m-d H:i:s',$contentRow['ctime']);
                   $content = '你于'.$time.'申请的报销工单号：'.$contentRow['sno'].'已被驳回,驳回原因:'.$contentRow['decline_remark'];
                   has_oper($content,$contentRow['sale_id'],'','消息提醒','系统消息');
                   not_oper($contentRow['sno'],session('USERINFO.id'));
                    //销售人员做处理
                    /*$msg_content = '您有一条报销工单号：'.$row['sno'].' 待处理';
                    has_oper($msg_content,$row['sale_id']);*/

               }else if($data['status'] == 2){
                   //NewMessage::remind(10,$row['sale_id']);
                   $contentRow = M('Reimbursement')->where(array('id'=>$data['id']))->find();
                   $time = date('Y-m-d H:i:s',$contentRow['ctime']);
                   $content = '你于'.$time.'申请的报销工单号：'.$contentRow['sno'].'已审核通过';
                   has_oper($content,$contentRow['sale_id'],'','消息提醒','系统消息');
                   not_oper($contentRow['sno'],session('USERINFO.id'));

                    /* 平台财务经理做处理 */
                    //平台财务经理
                    $user_idarr = M('User')->field('id')->where(array('role_id'=>6,'status'=>1))->select();
                    if($user_idarr){
                        $oper_title  = '待处理报销单';
                        $msg_content = '您有一条报销工单号：'.$row['sno'].' 待处理';
                        $user_ids = implode(',', i_array_column($user_idarr,'id'));
                        $oper_url = 'Reimbursement/index';
                        has_oper($msg_content,$user_ids,$oper_url,$oper_title);
                    }
               }
            }
            $this->admin_ajax_return($re_status);
        }

    }


    //打款
    public function play_money(){
        if(IS_GET){
            $id = I("get.id");
            $info = $this->model->getInfo($id);
            $this->assign($info);
            $this->display('play_money');
        }else{
            $data = I("post.");
            if(empty($data['give_img'])){
                ajax_return(0,'请上传打款凭证');
            }else{
                $data['give_img'] = implode('|',$data['give_img']);
                $data['status'] = 3;
                $re_status = $this->model->save($data);
                if($re_status){
                    $sno = $this->model->getFieldById($data['id'],'sno');
                    not_oper($sno,session('USERINFO.id'));

                    $sale_id = $this->model->where(array('id'=>$data['id']))->getField('sale_id');
                    NewMessage::remind(10,$sale_id);
                }
                $this->admin_ajax_return($re_status);
            }

        }
    }

    //查看
    public function cat(){
        //获取详情
        $id = I("get.id");
        $role_id = I("get.role_id");
        $info = $this->model->getInfo($id);
        $this->assign('role_id',$role_id);
        $this->assign($info);
        $this->display();
    }


    protected function _before_edit_view(){
        //报销类型
        $reimbursementTypes = M("ReimbursementType")->select();
        $this->assign('reimbursementTypes',$reimbursementTypes);
        //酒店
        $userinfo = session('USERINFO');
        $hotelGets = M("HotelGet")->where(array('sale_id'=>$userinfo['id'],'is_default'=>1))->select();
        $h_ids = array();
        foreach($hotelGets as $va){
            if($va['h_id'] != 0){
                $h_ids[] = $va['h_id'];
            }
        }
        $hotels = array();
        if(!empty($h_ids)){
            $where = array('status'=>1,'id'=>array('in',$h_ids));
            $hotels = M("Hotel")->where($where)->select();
        }

        $this->assign('hotels',$hotels);
//        //合同
//        $hotelContracts = M("HotelContract")->select();
//        $this->assign('hotelContracts',$hotelContracts);
    }


    //获取酒店对应的合同
    public function get_hotelcontract(){
        $h_id = I("get.h_id");
        $hotelContracts = M("HotelContract")->field('id,name')->where(array('h_id'=>$h_id))->select();
        $this->ajaxReturn($hotelContracts);
    }



}