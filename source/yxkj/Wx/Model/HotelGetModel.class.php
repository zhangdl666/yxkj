<?php
/**
 * Created by PhpStorm.
 * User: 刘小伟
 * Date: 2017/9/19
 * Time: 11:14
 */
namespace Wx\Model;
use Wx\Model\BaseModel;
use Pc\Server\PageModel;
use Pc\Model\HotelModel;
class HotelGetModel extends BaseModel
{
    public $model= 'Hotel';
    // 定义表名
    const TABLENAME_HOTEL = 'hotel';
    const TABLENAME_USER = 'user';
    const TABLENAME_TYPE = 'accounts_type';
    // 定义状态枚举类
    const STATUS_ONE = 1;  // 我已认领
    const STATUS_TWO = 2;  // 已认领
    const STATUS_THREE = 3;  //未认领
    //定义验证规则
    protected $_validate = array(
        array('name','require','酒店名不能为空!'),
        array('name','','名称已经存在！',0,'unique',1),
        array('ht_id','require','请选择酒店类型!'),
        array('provice','require','请选择地区!'),
        array('area','require','请填写详细地址!'),
        array('tell', 'require', '酒店联系方式不能为空！'),
        //array('tell', '/^\d{3}[0-9|-]\d{4,7}$/ ', '请重新填写酒店联系方式！'),
        //  array('tell','checkMobile','请输入正确的酒店联系方式',0,'function'),
        array('shang_tell','/^1[3-9][0-9]\d{4,8}$/','请重新填写酒店商务负责人联系方式!',2),
        array('all_tell','/^1[3-9][0-9]\d{4,8}$/','请重新填写酒店酒店总经理联系方式!',2),
        array('money_tell','/^1[3-9][0-9]\d{4,8}$/','请重新填写酒店酒店财务负责人联系方式!',2),
        array('project_tell','/^1[3-9][0-9]\d{4,8}$/','请重新填写酒店酒店工程负责人联系方式!',2),
    );
    /**
     *显示主页数据和分页
     */
    public function listDate($p,$where){
        if($where){
            $keywords = $where['keywords'];
            $wheres['o.name '] = ["like",'%'."$keywords".'%'];
           if($where['is_get'] == -1){
               $wheres['o.is_get'] = 0;
           }
            if($where['is_get'] == 1){
                $wheres['o.is_get'] = 1;
            }
           if($where['ht_id']){
               $wheres['o.ht_id'] =$where['ht_id'];
           }
        }
        $count =M('Hotel') ->where($where) -> count();
        $listRows =C('PAGE_SIZE');
        $page = new PageModel($count, $listRows);  //自己获取请求中的页面直接使用
        $page->lastSuffix = false;
        $pageHtml = $page->show();
        $data = M('Hotel')
            ->alias('o')
            ->join('left join '.'yx_hotel_type as a on a.id = o.ht_id')
            ->join('left join '.'yx_hotel_get as h on h.id = o.id')
            ->field('o.*,a.name as type_name,h.sale_id')
            ->where($wheres)
            ->page($page->firstRow=$p,$page->listRows)
            ->select();
        return array('pageHtml'=>$pageHtml,'items'=>$data);
    }

    /**
     * 我的认领
     * @param $user_id
     * @return mixed
     */
    public static function getMyListHotel($user_id)
    {
        $where['l.sale_id'] =  $user_id;
        $where['l.is_default'] = 1;
        $where['p.status'] = 1;
        $data =  M('HotelGet')
            ->alias('l')
            ->join('left join ' .'yx_hotel as p on p.id = l.h_id')
            ->join('left join ' .'yx_hotel_type as s on s.id = p.ht_id')
            ->where($where)
            ->field('p.id,p.name,p.is_sign,l.is_default,s.name as type_name,p.img,l.ctime')
            ->order('l.ctime desc')
            ->select();
        //查看我的认领时，将认领的未签约状态下超过三十天的酒店改为未认领状态供其它人认领
        $num = i_array_column($data,'id');
        foreach ($num as $key=>$val){
          if($data[$key]['is_sign'] == 0){
              $number =  HotelModel::claimHotelDate($val);
              if(empty($number)){
                  unset($data[$key]);
              }
          }
        }
        return $data;
    }
    //编辑数据
    public  function editor($data){
        if (!$this->create($data)){
            return ($this->getError());
        }else{
             return M('Hotel') ->where(array('id' =>$data['id'])) ->setField($data);
           }
    }
    /*
     * 单条数据
     *
     * */
    public static function oneDate($where){
        //先进行处理，将未签约认领时间超过三十天的酒店改为未认领状态
        $id =$where['o.id'];
         HotelModel::claimHotelDate($id);
        //查询酒店基本情况
        $data = M('Hotel')
            ->alias('o')
            ->join('left join '.'yx_hotel_get as h on h.h_id = o.id')
            ->join('left join '.'yx_hotel_type as a on a.id = o.ht_id')
            ->field('o.*,a.name as type_name,a.name as hotel_name,h.ctime as hotel_stime')
            ->where($where)
            ->order('h.ctime desc')
            ->find();
        //查询酒店不同房间类型的房间数
        $room_type = D('RoomType')->getList();
        $hrt_items = D('HotelrtRoomtView')->where(array('h_id'=>$data['id']))->select();
        foreach ($room_type as &$value) {
            foreach ($hrt_items as $hrtk => $hrtval) {
                if($value['id'] == $hrtval['id']){
                    $value['room_num'] = $hrtval['room_num'];
                }
            }
        }
        $data['roomer'] = $room_type;
            //查询酒店最近三次历史认领记录
        $data['hostoryer']  = M('HotelGet')
            ->alias('n')
            ->join('yx_user as u on u.id = n.sale_id')
            ->field('u.real_name as username,n.is_default')
            ->where(array('n.h_id' =>$data['id']))
            ->order('n.ctime desc')
            ->limit(3)
            ->select();
        //酒店当前认领
       foreach( $data['hostoryer'] as $k =>$v){
           if($v['is_default'] == 1 ){
               $data['cliam'] = $v['username'];
           }else{
               $data['hostory'][]  = $v['username'];
           }
        }
        //酒店认领有效期
        if($data['is_get'] == 1){
            $data['intime'] =ceil(($data['hotel_stime'] +30*86400 -time())/86400);
        }
        //处理图片与缩略图
        $data['imgs'] = explode(',',$data['img']);
        $data['thumb_imgs'] = explode(',',$data['thumb_img']);
        return $data;
    }
    /*
     *酒店类型
     * */
    public static function Hoteltype(){
        return M('HotelType') ->field('id,name') ->order('sort,ctime desc') ->select();
    }


}
