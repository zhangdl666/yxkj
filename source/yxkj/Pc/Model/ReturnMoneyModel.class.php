<?php
/**
 * ReturnMoneyModel.class.php
 * 回款类型
 * @author baddl
 * @date   2017-09-06 16:29
 */
namespace Pc\Model;
use Pc\Server\PageModel;

class ReturnMoneyModel extends BaseModel{
    protected $_validate = array(
        array('price','require','实际到账金额不能为空!'),
        array('price','/^[0-9]+(.[0-9]{1,2})?$/','实际到账金额必须是正数!'),
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


    /**
     * 获取所有统计类型
     */
    public function counter(){
        $ReturnMoney = array();
        //统计未打款次数
        $ReturnMoney['num'] = ( self::getAllCountHotel(1) +  self::getAllCountHotel(2));
        //未打款金额
        $ReturnMoney['noMoney'] = number_format((self::MtimeMoney(1)+self::MtimeMoney(2)),2);
        //统计已打款次数
        $ReturnMoney['timeNum'] = self::getAllCountHotel(3);
        //已打款金额
        $ReturnMoney['inMoney'] = number_format(self::MtimeMoney(3),2);
        //统计总单数
        $ReturnMoney['allNum'] = ( $ReturnMoney['num'] +   $ReturnMoney['timeNum'] );
        //统计总金额
        $ReturnMoney['allMoney'] =number_format((self::MtimeMoney(1)+self::MtimeMoney(2)+  self::MtimeMoney(3)),2);
        return $ReturnMoney;

    }

    /**
     * 统计次数
     * @return mixed
     */
    public static function getAllCountHotel($id)
    {
        switch($role = $_SESSION['USERINFO']){
            case($role['role_id'] ==  2):
                $arr = M('HotelGet') ->field('h_id') ->where(array('sale_id' =>$role['id'],'is_default' =>1)) ->select() ;
                foreach ($arr as $k =>$v){
                    $array[] = $v['h_id'];
                }
                if(is_array($array)) {
                    $where['o.status'] =$id;
                    $where['o.h_id'] = array('in',$array);
                }else{
                    return 0;
                }
                return   M('ReturnMoney')  ->alias('o') ->where($where)  ->count();
                break;
            case($role['role_id'] == 10):
                $where['o.status'] =$id;
                $where['h_id'] =$_SESSION['USERINFO']['hotel_id'];
                return M('ReturnMoney')  ->alias('o') ->where($where)  ->count();
                break;
            case($role['role_id'] ==  3):
                $xiaoshou = M('User') ->where(array('parent_id'=>$role['id']))->field('id') ->select();
                foreach ($xiaoshou as $key =>$val){
                    $arr = M('HotelGet') ->field('h_id') ->where(array('sale_id' =>$val['id'],'is_default' =>1)) ->select() ;
                    foreach ($arr as $k =>$v){
                        $array[] = $v['h_id'];
                    }
                }
                if(is_array($array)) {
                    $where['status'] =$id;
                    $where['h_id'] = array('in',$array);
                    return M('ReturnMoney')  ->where($where) ->count();
                }else{
                    return 0;
                }
                break;
            default:
                return  M(self::TABLENAME_MONEY)->where(array("status"=>$id) )->count();
        }
    }
    /**
     * 统计金额
     * @return mixed
     */
    public static function MtimeMoney($run)
    {
        if($run == 1 || $run ==2){
            $files = 'rprice,mprice,price';
        }else{
            $files = 'price';
        }
        switch($role = $_SESSION['USERINFO']){
            case($role['role_id'] ==  2):
                $arr = M('HotelGet') ->field('h_id') ->where(array('sale_id' =>$role['id'],'is_default' =>1)) ->select() ;
                foreach ($arr as $k =>$v){
                    $array[] = $v['h_id'];
                }
                if(is_array($array)) {
                    $where['status'] =$run;
                    $where['h_id'] = array('in',$array);
                    $rmoney_items = M('ReturnMoney')  ->where($where)  ->field($files)  ->select();
                }else{
                    return $money = 0;
                }
                break;
            case($role['role_id'] ==  10):
                $where['status'] =$run;
                $where['h_id'] =$_SESSION['USERINFO']['hotel_id'];
                $rmoney_items = M('ReturnMoney')
                    ->where($where)
                    ->field($files)
                    ->select();
                break;
            case($role['role_id'] ==  3):
                $xiaoshou = M('User') ->where(array('parent_id'=>$role['id']))->field('id') ->select();
                foreach ($xiaoshou as $key =>$val){
                    $arr = M('HotelGet') ->field('h_id') ->where(array('sale_id' =>$val['id'],'is_default' =>1)) ->select() ;
                    foreach ($arr as $k =>$v){
                        $array[] = $v['h_id'];
                    }
                }
                if(is_array($array)) {
                    $where['status'] =$run;
                    $where['h_id'] = array('in',$array);
                    $rmoney_items = M('ReturnMoney')  ->where($where)  ->field($files)  ->select();
                }else{
                    $rmoney_items = 0;
                }
                break;
            default:
                $rmoney_items = M('ReturnMoney')->field($files)->where(array("status"=>$run))->select();
        }
        $money = 0;
        foreach ($rmoney_items as $key => $value) {
            if($run == 1 || $run ==2){
                $money += $value['rprice'] /*+= $value['mprice'] */;
            }else{
                $money += $value['price'] ;
            }
        }
        return $money;
    }
    /*
     * 首页数据
     * 分页
     * */
    public function getResultLister($rother,$p){
        if(is_numeric($rother)){
            $where =array();
            $where['o.status'] = $rother;
        }else{
            $where = '';
        }
        switch($role = $_SESSION['USERINFO']){
            case($role['role_id'] ==  2):
                $arr = M('HotelGet') ->field('h_id') ->where(array('sale_id' =>$role['id'],'is_default' =>1)) ->select() ;
                foreach ($arr as $k =>$v){
                    $array[] = $v['h_id'];
                }
                if(is_array($array)) {
                    $where['o.h_id'] = array('in',$array);
                }else{
                    return array('items'=>null);
                }
                break;
            case($role['role_id'] ==  10):
                $where['o.h_id'] =$_SESSION['USERINFO']['hotel_id'];
                break;
            /*case($role['role_id'] ==  3):
                $xiaoshou = M('User') ->where(array('parent_id'=>$role['id']))->field('id') ->select();
                foreach ($xiaoshou as $key =>$val){
                    $arr = M('HotelGet') ->field('h_id') ->where(array('sale_id' =>$val['id'],'is_default' =>1)) ->select() ;
                    foreach ($arr as $k =>$v){
                        $array[] = $v['h_id'];
                    }
                }
                if(is_array($array)) {
                    $where['o.h_id'] = array('in',$array);
                }else{
                    return array('items'=>null);
                }
                break;*/
        }
        $count =M('ReturnMoney') ->alias('o')->where($where) ->count();
        $listRows =C('PAGE_SIZE');
        $page = new PageModel($count, $listRows);  //自己获取请求中的页面直接使用
        $page->lastSuffix = false;
        $pageHtml = $page->show();
        $data = M('ReturnMoney')
            ->alias('o')
            ->join('yx_hotel as h on h.id = o.h_id')
            ->join('yx_accounts_type as t on t.id = o.at_id ' )
            ->join('yx_hotel_contract as c on c.id = o.hc_id ' )
            ->where($where)
            ->field('o.*,h.name,t.type,t.price as type_price,c.name as contract_name,c.sno as contract_sno,c.ls_id,c.month_price')
            ->order('o.status asc,o.mtime desc')
            ->page($page->firstRow=$p,$page->listRows)
            ->select();
        //处理数据
        foreach ($data as $key=>$val){
            $data[$key]['arr_giv'] =explode(',', $val['give_img']);
            $data[$key]['arr_git'] =explode(',', $val['get_img']);
            //格式化总价
            $data[$key]['rprice'] = number_format(($data[$key]['rprice']),2);
        }
        $this ->_handleRows($data);
        return array('pageHtml'=>$pageHtml,'items'=>$data);
    }

    /**
     * 结算/到账/查看展示之前准备数据
     * $id为ReturnMoney中的id
     */
    public static function inView($id){
        $data = M('ReturnMoney')
            ->alias('o')
            ->join('yx_hotel as h on h.id = o.h_id')
            ->join('yx_accounts_type as t on t.id = o.at_id ' )
            ->join('yx_hotel_contract as c on c.id = o.hc_id ' )
            ->field('o.*,h.name,t.type,t.price as type_price,c.name as contract_name,c.sno as contract_sno,c.ls_id')
            ->where('o.id = '.$id )
            ->find();
        if(!empty($data['give_img'])){
          $data['arr_giv'] = explode(',', $data['give_img']);  
        }
        if(!empty($data['get_img'])){
          $data['arr_git'] = explode(',', $data['get_img']);  
        }
        
        //格式化总价
        $data['rprice'] =$data['rprice'];
        //格式化滞纳金
        $data['mprice'] =$data['mprice'];
        $data['user_role'] = $_SESSION['USERINFO']['role_id'];
        $user_id = $_SESSION['USERINFO']['id'];
        //获取当前人员与该酒店之间的关系
        $data['status_id'] =HotelUserModel::checkUserStatus($user_id,$data['h_id']);
        return $data;
    }


}