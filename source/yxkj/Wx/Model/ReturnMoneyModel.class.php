<?php
/**
 * ReturnMoneyModel.class.php
 * 回款类型
 * @author baddl
 * @date   2017-09-06 16:29
 */
namespace Wx\Model;
use Pc\Server\PageModel;
use Pc\Model\HotelUserModel;
class ReturnMoneyModel extends BaseModel{
    protected $_validate = array(
        array('price','require','请填写回款金额!'),
        array('price','/^[0-9]+(.[0-9]{1,2})?$/','实际回款金额必须是正数!'),
    );
    // 定义表名
    const TABLENAME_HOTEL = 'hotel';
    const TABLENAME_MONEY = 'return_money';
    const TABLENAME_USER = 'user';
    const TABLENAME_TYPE = 'accounts_type';

    // 定义状态枚举
    const STATUS_ONE = '3';   // 待结算
    const STATUS_TWO = '2';    // 到账确认
    const STATUS_THREE = '1';  // 已结算
    /*
     * 首页数据
     * 处理
     * */
    /*public static function getResultLister($data){
        foreach ($data as $k =>$v){
            
        }
        return $data;
    }*/
    public function getList($model,$wheres=null,$order_str=null){
        $datas = $this->getDatas($model,$wheres,1,$order_str);
        /*$datas =ReturnMoneyModel::getResultLister($data);*/
        session('MODEL',$model);
        session('WHERES',$wheres);
        session('ORDER',$order_str);

        //>>6.分配数据
        $re_datas = $this->limitDatas($datas,1,1);

        //对数据进行处理
        if($re_datas){
            $this->_handleRows($re_datas);
        }

        //>>6.1序列化保存在session里面
        $datas = serialize($datas);
        session('DATAS', $datas);
        return $re_datas;
    }

    /**
     * 对数据进行处理
     * @param  array  $datas    数据
     */
    protected function _handleRows(&$datas){
        foreach ($datas['datas'] as &$value) {
            if($value['give_img']){
                $value['arr_giv'] =explode(',', $value['give_img']);
            }
            
            if($value['get_img']){
                $value['arr_git'] =explode(',', $value['get_img']);
            }
            if($value['hotel_img']){
                $hotel_img = explode(',', $value['hotel_img']);
                $value['hotel_img'] = $hotel_img[0];
            }
            $value['rtime'] = date('Y-m-d',$value['rtime']);
        }
    }

    /**
     * 结算/到账/查看展示之前准备数据
     */
    public static function inView(){
        $id =I('id');
        $data = M('ReturnMoney')
            ->alias('o')
            ->join('yx_hotel as h on h.id = o.h_id')
            ->join('yx_hotel_type as a on a.id = h.ht_id')
            ->join('yx_accounts_type as t on t.id = o.at_id ' )
            ->join('yx_hotel_contract as c on c.id = o.hc_id ' )
            ->field('o.*,h.name,t.type,h.img as hotel_img,a.name as type_name,t.type,t.price as type_price,c.name as contract_name,c.sno as contract_sno,c.ls_id')
            ->where('o.id = '.$id )
            ->find();
        if($data['give_img']){
            $data['arr_giv'] =explode(',', $data['give_img']);
        }
        if($data['get_img']){
          $data['arr_git'] =explode(',', $data['get_img']);  
        }
        
        $data['arr_hotel'] =explode(',', $data['hotel_img']);
        //格式化总价
        $data['rprice'] =$data['rprice'];
        $data['user_role'] = $_SESSION['USERINFO']['role_id'];
        $data['h_img'] =  $data['arr_hotel'][0];
        $user_id = $_SESSION['USERINFO']['id'];
        //获取当前人员与该酒店之间的关系
        $data['status_id'] =HotelUserModel::checkUserStatus($user_id,$data['h_id']);
        return $data;
    }

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
    /*public function showMoreDatas($key, $num = 10){
        $data = session('DATAS');
        $data = unserialize($data);
        $page = I('get.page') > 1 ? I('get.page') : 1;
        $get_datas = $this->limitDatas($data, $page, $key, $num);
        //对数据进行处理
        if($get_datas){
            $this->getResultLister($get_datas['datas']);
        }

        return $get_datas;
    }*/
}