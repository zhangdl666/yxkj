<?php
/**
 * Created by PhpStorm.
 * User: hjw
 * Date: 2017/9/25
 * Time: 16:06
 */

namespace Wx\Model;


class SaleGetmoneyModel extends BaseModel
{
    //添加提现的验证规则
    protected $_validate = array(
        array('rprice', 'require', '申请金额不能为空'),
        array('rprice','/^[0-9]+(.[0-9]{1,2})?$/','请重新填写申请金额!'),
    );

    /**
     * 获取数据
     * @param  array  $data
     * @return array
     */
    public function getRmList($model,$wheres=null,$order_str=null){
        $datas = $this->getDatas($model,$wheres,1,$order_str);
        session('MODEL',$model);
        session('WHERES',$wheres);
        session('ORDER',$order_str);

        //>>6.分配数据
        $re_datas = $this->limitDatas($datas,1,1);

        //对数据进行处理
        if($re_datas){
            $this->_rmHandleRows($re_datas);
        }

        //>>6.1序列化保存在session里面
        $datas = serialize($datas);
        session('DATAS', $datas);
        return $re_datas;
    }

    /**
     * ajax获取加载数据
     * @param $key 页码
     * @param $num 加载个数
     * @return array
     */
    public function showMoreDatas($key, $num = 10){
        $data = session('DATAS');
        $data = unserialize($data);
        $page = I('get.page') > 1 ? I('get.page') : 1;
        $get_datas = $this->limitDatas($data, $page, $key, $num);
        return $get_datas;
    }
    /**
     * 打款/查看展示之前准备数据
     */
    public static function  inView()
    {
        $id = I('id');
        //试图关联查询销售人员提现情况，用户，提现账户，报销情况查询所需字段
        $data = M('SaleGetmoney')
            ->alias('o')
            ->join('yx_user  as u on u.id = o.sale_id')
            ->join('yx_account as a on a.id =o.account_id')
            ->join('yx_bank_info as b on b.id =a.bank_type')
            ->field('o.*,u.name,a.bank_type,a.bank_num,b.name as bank_name')
            ->order('ctime desc')
            ->where('o.id=' . $id)
            ->find();
        $data['user_priceser'] =M('Reimbursement') ->where(array('sale_id'=>$data['sale_id']))->field('price')->find();
        //计算到账金额 =申请金额-个人所得税
        // $data['user_price'] = $data['user_priceser']['price'];
        $data['in_price'] = $data['rprice'] - $data['mprice'] ;
        //组装查看时打款凭证
        if ($data['give_img'] != '') {
            $data['arr_img'] = explode(',', $data['give_img']);
        }
        //格式化金额
        $data['in_price'] = number_format($data['in_price'], 2);
        return $data;
    }
    /*
     * 计算未处理事务
     * */
    public function getNoHaddleNum(){
        $user = $_SESSION['USERINFO'];
        if($user['role_id'] ==6){
            $num = M('SaleGetmoney') -> where(array('status' =>1)) ->count();
        }
        return $num;
    }
}