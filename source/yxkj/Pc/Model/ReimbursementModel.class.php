<?php
/**
 * ReimbursementModel.class.php
 * 报销列表
 * @author: wy901216
 * @date: 2017/9/5  11:08
 */
namespace Pc\Model;
use Pc\Server\PageModel;
use Think\Page;

class ReimbursementModel extends BaseModel{
    // 自动验证
    protected $_validate = array(
        array('rt_id','require','报销类型不能为空!'),
        array('price','require','报销金额不能为空!'),
        array('h_id','require','酒店不能为空!'),
    );

    public function getResultList($page,$wheres){
        $this->alias('obj');
        $this->_setModel();
        $count = $this->where($wheres)->count();
        $listRows = C('PAGE_SIZE');
        $PcPage = new PageModel($count,$listRows);
        $pageHtml = $PcPage->show();

        $this->alias('obj');
        $this->_setModel();
        $re_datas = $this->where($wheres)->page($page . ',' . $listRows)->order('obj.ctime desc')->select();
        //对数据进行处理
        $this->_handleRows($re_datas);

        return array('page'=>$pageHtml,'items'=>$re_datas);
    }

    protected function _setModel(){
        $this->field('obj.*,b.real_name as submitter,c.name as reimbursement_type_name,d.name as hotel_name');
        $this->join('yx_user as b on b.id=obj.sale_id');
        $this->join('yx_reimbursement_type as c on c.id=obj.rt_id');
        $this->join('yx_hotel as d on d.id=obj.h_id');
        //$this->join('yx_hotel_contract as e on e.id=obj.hc_id');
    }

    protected function _handleRows(&$datas){
        $model = M("HotelContract");
        foreach($datas as &$data){
            $row = $model->where(array('id'=>$data['hc_id']))->find();
            $data['hotel_contract_name'] = $row['name'];
        }
    }


    public function getStatisticsData($wheres){
        $rows = $this->alias('obj')->where($wheres)->select();
        $data['dsh_num'] = 0;      //待审核报销工单
        $data['ddk_num'] = 0;      //待打款报销工单
        $data['ydk_num'] = 0;      //已打款报销工单
        $data['ybh_num'] = 0;      //已驳回报销工单
        $data['qb_num'] = 0;       //全部报销工单
        $data['dsh_money'] = 0;       //待审核金额
        $data['ddk_money'] = 0;       //待打款金额
        $data['ydk_money'] = 0;       //已打款金额
        $data['total_money'] = 0;       //报销申请总金额

        if(!empty($rows)){
            foreach($rows as $value){
                switch($value['status']){
                    case '1' :      //待审核
                        $data['dsh_num']+=1;
                        $data['dsh_money']+=$value['price'];
                        break;
                    case '2' :      //待打款
                        $data['ddk_num']+=1;
                        $data['ddk_money']+=$value['price'];
                        break;
                    case '3' :      //已打款
                        $data['ydk_num']+=1;
                        $data['ydk_money']+=$value['price'];
                        break;
                    case '4' :      //已驳回
                        $data['ybh_num']+=1;
                        break;
                }
                $data['total_money']+=$value['price'];
            }
            $data['qb_num']+= count($rows);
        }

        return $data;
    }


    public function operation(){
        if(I('post.price')){
            $reg = '/(^[1-9]([0-9]+)?(\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\.[0-9]([0-9])?$)/';
            if(!preg_match($reg,I('post.price'))){
                $this->error = '报销金额不正确';
                return false;
            }
        }

        $id = I('post.id');
        //添加
        if(empty($id)){
            $sno = get_reimbursement_sn();
            $this->data['sno'] = $sno;
            $userInfo = session('USERINFO');
            $this->data['sale_id'] = $userInfo['id'];
            $this->data['ctime'] = time();
            $re_status = $this->add();

            //销售经理做处理
            $oper_title  = '待处理报销单';
            $msg_content = '您有一条报销工单号：'.$sno.' 待处理';
            $oper_url = 'Reimbursement/index';
            has_oper($msg_content,$userInfo['parent_id'],$oper_url,$oper_title);
        }
        //编辑
        else{
            $is_exsits = $this->field('id,status')->where(I('post.'))->find();
            if($is_exsits){
                return true;
            }
            if(I('post.status') == 1 && $is_exsits['status']!=I('post.status')){
                $sno = $this->getFieldById($id,'sno'); 
                //销售经理做处理
                $oper_title  = '待处理报销单';
                $msg_content = '您有一条报销工单号：'.$sno.' 待处理';
                $oper_url = 'Reimbursement/index';
                has_oper($msg_content,session('USERINFO.parent_id'),$oper_url,$oper_title);
            }elseif(I('post.status') == 5){
                $sno = $this->getFieldById($id,'sno');
                not_oper($sno,session('USERINFO.parent_id'));
            }

            $re_status = $this->save();

        }

        return $re_status;
    }


    public function getInfo($id){
        //报销基本信息
        $row = $this->alias('obj')
            ->field('obj.*,b.name as submitter,c.name as reimbursement_type_name,d.name as hotel_name')
            ->join('yx_user as b on b.id=obj.sale_id')
            ->join('yx_reimbursement_type as c on c.id=obj.rt_id')
            ->join('yx_hotel as d on d.id=obj.h_id')
            //->join('yx_hotel_contract as e on e.id=obj.hc_id')
            ->where(array('obj.id'=>$id))
            ->find();

        $row['total_price'] = $row['all_price'];       //累计申请的报销金额
        $row['claimed_hotel_num'] = $row['hotel_num'];       //已认领的酒店数
        $row['sign_contract_num'] = $row['hc_num'];       //已签订的合同数
        $row['payment_money'] = $row['rm_price'];       //合同已回款金额
        $row['basic_time'] = $row['now_time'];       //基本信息提取时间

        $row['hotel_contract_name'] = M("HotelContract")->where(array('id'=>$row['hc_id']))->getField('name');

        //打款凭证
        if(!empty($row['give_img'])){
            $imgs = array();
            $voucher = explode("|",$row['give_img']);
            if(!empty($voucher)){
                for($i = 0 ;$i < count($voucher); $i++){
                    $imgs[$i]['voucher_img'] = $voucher[$i];
                }
            }
            $row['imgs'] = $imgs;
        }

        if(($row['status'] == 1) || ($row['status'] == 5)){
            //报销人基本情况
            $total_price = 0;
            $prices = $this->field("SUM(price) as price")->where(array('sale_id'=>$row['sale_id'],'status'=>3,'ctime'=>array('elt',time())))->select();
            $total_price+=$prices[0]['price'];
            $row['total_price'] = $total_price;     //累计申请的报销金额

            $claimed_hotels = M("HotelGet")->where(array('sale_id'=>$row['sale_id'],'is_default'=>1))->select();
            $row['claimed_hotel_num'] = count($claimed_hotels);    //已认领的酒店数

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

            $row['sign_contract_num'] = $sign_contract_num;     //已签订的合同数
            $row['payment_money'] = $payment_price;        //合同已回款金额
            $row['basic_time'] = time();       //基本信息提取时间
        }
        return $row;
    }
}