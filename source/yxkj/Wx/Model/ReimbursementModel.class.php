<?php
/**
 * ReimbursementModel.class.php
 * �����б�
 * @author: wy901216
 * @date: 2017/9/22  11:20
 */

namespace Wx\Model;


class ReimbursementModel extends BaseModel{
    // �Զ���֤
    protected $_validate = array(
        array('rt_id','require','报销类型不能为空'),
        array('price','require','报销金额不能为空'),
        array('h_id','require','酒店id不能为空'),
        array('price','checkPrice','报销金额不正确',0,'callback')
    );
    protected function checkPrice(){

        if(is_numeric($_POST['price']) && $_POST['price'] > 0){
            return true;
        }else{
            return false;
        }
    }
    protected function _handleRows(&$re_datas){
        foreach ($re_datas['datas'] as &$val){
            //获取酒店名称
            $val['h_name'] = M('Hotel')->where(['id'=>$val['h_id']])->getField('name');
            //获取报销申请人的名称
            $val['sale_name'] = M('User')->where(['id'=>$val['sale_id']])->getField('real_name');
            //获取报销类型的名称
            $val['rt_name'] = D('reimbursement_type')->where(['id'=>$val['rt_id']])->getField('name');
            //获取该报销合同的名称
            if(!empty($val['hc_id'])){
                $val['hc_name'] = M('HotelContract')->where(['id'=>$val['hc_id']])->getField('name');
            }
        }
    }


    public function operation(){
        $h_id = I('post.h_id');
        if(I('post.price')){
            $reg = '/(^[1-9]([0-9]+)?(\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\.[0-9]([0-9])?$)/';
            if(!preg_match($reg,I('post.price'))){
                $this->error = '报销金额不正确';
                return false;
            }  
        }
        if(empty($h_id)){
            $this->error = '请选择酒店';
            return false;
        }
        
        $id = I('post.id');
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
            }

            $re_status = $this->save();
        }
        return $re_status;
    }


    public function getInfo($id){
        //����������Ϣ
        $row = $this->alias('obj')
            ->field('obj.*,b.real_name as submitter,c.name as reimbursement_type_name,d.name as hotel_name')
            ->join('yx_user as b on b.id=obj.sale_id')
            ->join('yx_reimbursement_type as c on c.id=obj.rt_id')
            ->join('yx_hotel as d on d.id=obj.h_id')
            //->join('yx_hotel_contract as e on e.id=obj.hc_id')
            ->where(array('obj.id'=>$id))
            ->find();
        $row['total_price'] = $row['all_price'];       //�ۼ�����ı������
        $row['claimed_hotel_num'] = $row['hotel_num'];       //������ľƵ���
        $row['sign_contract_num'] = $row['hc_num'];       //��ǩ���ĺ�ͬ��
        $row['payment_money'] = $row['rm_price'];       //��ͬ�ѻؿ���
        $row['basic_time'] = $row['now_time'];       //������Ϣ��ȡʱ��

        $row['hotel_contract_name'] = M("HotelContract")->where(array('id'=>$row['hc_id']))->getField('name');

        //���ƾ֤
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
            //�����˻������
            $total_price = 0;
            $prices = $this->field("SUM(price) as price")->where(array('sale_id'=>$row['sale_id'],'status'=>3,'ctime'=>array('elt',time())))->select();
            $total_price+=$prices[0]['price'];
            $row['total_price'] = $total_price;     //�ۼ�����ı������

            $claimed_hotels = M("HotelGet")->where(array('sale_id'=>$row['sale_id'],'is_default'=>1))->select();
            $row['claimed_hotel_num'] = count($claimed_hotels);    //������ľƵ���

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

            $row['sign_contract_num'] = $sign_contract_num;     //��ǩ���ĺ�ͬ��
            $row['payment_money'] = $payment_price;        //��ͬ�ѻؿ���
            $row['basic_time'] = time();       //������Ϣ��ȡʱ��
        }

        return $row;

    }


}