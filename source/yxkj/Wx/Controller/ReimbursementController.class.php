<?php
/**
 * ReimbursementController.class.php
 * 报销列表
 * @author: wy901216
 * @date: 2017/9/22  11:09
 */

namespace Wx\Controller;
use Pc\Server\NewMessage;

class ReimbursementController extends BaseController{
    public function _initialize(){
        parent::_initialize();
        $this->model = D("Reimbursement");

    }
    /*
     * 角色对应的待办事项个数
     ***/
    public function getNoHaddleNum(){
        $wheres = array();
        //获取当前用户角色
        $userinfo = session('USERINFO');
        switch($userinfo['role_id']){
            case 2 :             //平台销售人员对应的被驳回的数量
                $wheres['sale_id'] = $userinfo['id'];    //报销人员ID
                $wheres['status'] = 4;
                //数据查询
                $re_datas = $this->model->getList('Reimbursement',$wheres,$order_str='ctime desc');
                return count($re_datas['datas']);
                break;
            case 6 :       //平台财务经理  待打款的数据
                $wheres['status'] = 2;
                //数据查询
                $re_datas = $this->model->getList('Reimbursement',$wheres,$order_str='ctime desc');
                return count($re_datas['datas']);
                break;
            case 3 :       //平台销售经理  待审核的数据
                //获取该平台销售经理下面的平台销售人员
                $users = M("User")->where(array('parent_id'=>$userinfo['id'],'role_id'=>2))->select();
                if(!empty($users)){
                    $user_ids = array();
                    foreach($users as $user){
                        $user_ids[] = $user['id'];
                    }
                    if(!empty($user_ids)){
                        $wheres['sale_id'] = array('in',$user_ids);    //报销人员ID
                    }
                }
                $wheres['status'] = 1;

                //数据查询
                $re_datas = $this->model->getList('Reimbursement',$wheres,$order_str='ctime desc');
                return count($re_datas['datas']);
                break;
            case 9 :       //平台总经理 没有待办事项
                break;
        }
    }


    /*
     * 报销列表
     ***/
    public function index(){
        $type=I('get.type');
        $userInfo=session('USERINFO');
        $this->assign('role_id',$userInfo['role_id']);
        $where=array();
        if(empty($type)){
            if($userInfo['role_id'] == 6){
                $type=2;
            }else{
                $type=1;
            }
        }
        $where['status'] = $type;
        if($userInfo['role_id'] == 2){//平台销售人员
            $where['sale_id'] = $userInfo['id'];
        }elseif ($userInfo['role_id'] == 3){//平台销售经理
            $users = M("User")->where(array('parent_id'=>$userInfo['id'],'role_id'=>2))->select();
            if(!empty($users)){
                $user_ids = array();
                foreach($users as $user){
                    $user_ids[] = $user['id'];
                }
                if(!empty($user_ids)){
                    $where['sale_id'] = array('in',$user_ids);    //报销人员ID
                }
            }
            //获取待审核的数据
            $flagWhere=array(
                'sale_id'=>array('in',$user_ids),
                'status'=>1,
            );
            $flagNum=$this->model->where($flagWhere)->count();
            if($flagNum != 0){
                $this->assign('flagOne',1);
            }
        }elseif ($userInfo['role_id'] == 6){//平台财务经理
            //获取待打款的数据
            $flagWhere=array(
                'status'=>2,
            );
            $flagNum=$this->model->where($flagWhere)->count();
            if($flagNum != 0){
                $this->assign('flagTwo',1);
            }
        }
        $re_datas = $this->model->getList('Reimbursement',$where,$order_str='ctime desc');
        $this->assign($re_datas);
        cookie('__forwards__', $_SERVER['REQUEST_URI']);
        $this->assign('type',$type);
        $this->display();
    }

    public function indexOld(){
        //条件
        $status=I('get.status');
        if(!empty($status)){
            $this->assign('status',$status);
        }
        $wheres = array();
        //获取当前用户角色
        $userinfo = session('USERINFO');
        switch($userinfo['role_id']){
            case 2 :             //平台销售人员
                $wheres['sale_id'] = $userinfo['id'];//报销人员ID
                $flagWhere=array(
                    'sale_id'=>$userinfo['id'],
                    'status'=>4,
                );
                $flagNum=$this->model->where($flagWhere)->count();
                if($flagNum != 0){
                    $this->assign('flagFour',1);
                }
                break;
            case 6 :       //平台财务经理
                $wheres['status'] = array('in',array(2,3));
                $flagWhere=array(
                    'status'=>2,
                );
                $flagNum=$this->model->where($flagWhere)->count();
                if($flagNum != 0){
                    $this->assign('flagTwo',1);
                }
                break;
            case 3 :       //平台销售经理
                //获取该平台销售经理下面的平台销售人员
                $users = M("User")->where(array('parent_id'=>$userinfo['id'],'role_id'=>2))->select();
                if(!empty($users)){
                    $user_ids = array();
                    foreach($users as $user){
                        $user_ids[] = $user['id'];
                    }
                    if(!empty($user_ids)){
                        $wheres['sale_id'] = array('in',$user_ids);    //报销人员ID
                    }
                }
                $wheres['status'] = array('in',array(1,2,3,4));
                $flagWhere=array(
                    'sale_id'=>array('in',$user_ids),
                    'status'=>1,
                );
                $flagNum=$this->model->where($flagWhere)->count();
                if($flagNum != 0){
                    $this->assign('flagOne',1);
                }
                break;
            case 9 :       //平台总经理
                $wheres['status'] = array('in',array(1,2,3,4));
                break;
        }

        $this->assign('role_id',$userinfo['role_id']);

        //查询条件
        $this->_set_wheres($wheres);
        //数据查询
        $re_datas = $this->model->getList('Reimbursement',$wheres,$order_str='ctime desc');
        //echo '<pre>';
        //var_dump($re_datas);exit;
        $this->assign($re_datas);

        cookie('__forwards__', $_SERVER['REQUEST_URI']);
        $this->assign('page_title','首页');
        $this->display('index');
    }


    /*
     * 新增报销(只有平台销售人员有此权限)
     ***/
    public function add(){
        $this->_before_edit_view();
        $this->display('add');
    }
    /**
     * 撤回报销，只有平台销售人员有此权限
     */
    public function changeStatus(){
        $id=I('post.id');
        $result=$this->model->where(['id'=>$id])->setField('status',5);

        $sno = $this->model->getFieldById($id,'sno');
        not_oper($sno,session('USERINFO.parent_id'));

        $this->admin_ajax_return($result);
    }

    /*
     * 编辑报销(只有平台销售人员有此权限)
     ***/
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


    /*
     * 审核(只有平台销售经理有此权限)
     ***/
    public function audit(){
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
                $contentRow = M('Reimbursement')->where(array('id'=>$data['id']))->find();
                $time = date('Y-m-d H:i:s',$contentRow['ctime']);
                $content = '你于'.$time.'申请的报销已被驳回,驳回原因:'.$contentRow['decline_remark'];
                has_oper($content,$contentRow['sale_id'],'','消息提醒','系统消息');
                not_oper($contentRow['sno'],session('USERINFO.id'));
                
                //销售人员做处理
                /*$msg_content = '您有一条报销工单号：'.$row['sno'].' 待处理';
                has_oper($msg_content,$row['sale_id']);*/

           }else if($data['status'] == 2){
                $contentRow = M('Reimbursement')->where(array('id'=>$data['id']))->find();
                $time = date('Y-m-d H:i:s',$contentRow['ctime']);
                $content = '你于'.$time.'申请的报销已审核通过';
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

                /* 平台财务经理做处理 */
                /*$msg_content = '您有一条报销工单号：'.$row['sno'].' 待处理';
                //平台财务经理
                $user_idarr = M('User')->field('id')->where(array('role_id'=>6,'status'=>1))->select();
                $user_ids = implode(',', i_array_column($user_idarr,'id'));
                has_oper($msg_content,$user_ids);*/
           }
        }

        //dump($re_status);exit;
        $this->admin_ajax_return($re_status);

    }


    /*
     * 打款(只有平台财务经理有此权限)
     ***/
    public function play_money(){
        $data = I("post.");
        if(empty($data['give_img'])){
            ajax_return(0,'请上传打款凭证');
        }else{
            $data['give_img'] = implode('|',$data['give_img']);
            $data['status'] = 3;
            $re_status = $this->model->save($data);

            $sno = $this->model->getFieldById($data['id'],'sno');
            not_oper($sno,session('USERINFO.id'));

            $sale_id = $this->model->where(array('id'=>$data['id']))->getField('sale_id');
            NewMessage::remind(10,$sale_id);

            $this->admin_ajax_return($re_status);
        }
    }


    /*
     * 查看
     ***/
    public function cat(){
        //获取详情
        $id = I("get.id");
        $type=I('get.type');
        $this->assign('type',$type);
        //$mark = I("get.mark");
        $role_id = I("get.role_id");
        $info = $this->model->getInfo($id);
        $this->assign('role_id',$role_id);
        $this->assign($info);
        if(($info['status'] == 1) && ($role_id == 3)){      //平台销售经理，进入待审核界面
            $this->display('audit');
        }elseif(($info['status'] == 2) && ($role_id == 6)){     //平台财务经理，进入打款界面
            $this->display('play_money');
        }else{
            $this->display('cat');
        }

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

        //合同
        $hotelContracts = M("HotelContract")->field('id,name')->where(array('h_id'=>$hotels[0]['id']))->select();
        $this->assign('hotelContracts',$hotelContracts);
    }


    //获取酒店对应的合同
    public function get_hotelcontract(){
        $h_id = I("get.h_id");
        $result=array('code'=>0,'message'=>'获取失败');
        $hotelContracts = M("HotelContract")->field('id,name')->where(array('h_id'=>$h_id))->select();
        if($hotelContracts) {
            $result['code'] = 1;
            $result['message'] = $hotelContracts;
        }
        $this->ajaxReturn($result);
    }


}