<?php
/**
 * Author: ' Silent
 * Time: 2017/9/4 11:12
 */

namespace Pc\Model;


class HotelModel extends BaseModel
{
    // 定义表名
    const TABLENAME_HOTEL = 'hotel';
    const TABLENAME_GET = 'hotel_get';
    const TABLENAME_TYPE = 'hotel_type';
    const TABLENAME_ROOM = 'hotel_room_type';
    const TABLENAME_ROOMSE = 'hc_room_equipment';
    const TABLENAME_USER = 'user';
    const TABLENAME_ROOMS = 'room_type';
    const TABLENAME_CONTRO = 'hotel_contract';


    // 定义状态枚举
    const STATUS_ONE = '-1';   // 删除
    const STATUS_TWO = '0';    // 未营业
    const STATUS_THREE = '1';  // 营业

    const STATUS_YCLAIM = 1;  // 认领
    const STATUS_NCLAIM = 0;  // 未认领

    /**
     * 添加验证规则
     * @var array
     */
    public static function getAddRules()
    {
        $rules = array(
            array('name', 'require', '酒店名称不能为空'),
            array('ht_id', 'require', '请选择酒店类型'),
            array('provice', 'require', '请选择酒店省份'),
            array('city', 'require', '请选择酒店城市'),
            array('area', 'require', '请输入酒店详细地址'),
            array('tell', 'require', '请输入酒店联系方式'),
            //array('tell', '/^\d{3}[0-9|-]\d{4,7}$/ ', '请重新填写酒店联系方式！'),
            //array('tell','checkMobile','请输入正确的酒店联系方式',0,'function'),
            // 在新增的时候验证name字段是否唯一
            array('name', '', '酒店名称已经存在！', 0, 'unique', 1),
        );
        return $rules;
    }

    /**
     * 编辑验证规则
     * @return array
     */
    public static function getEditRules()
    {
        $rules = array(
            array('name', 'require', '酒店名称不能为空'),
            array('ht_id', 'require', '请选择酒店类型'),
            array('provice', 'require', '请选择酒店省份'),
            array('city', 'require', '请选择酒店城市'),
            array('tell', 'require', '请输入酒店联系方式'),
            //array('tell', '/^\d{3}[0-9|-]\d{4,7}$/ ', '请重新填写酒店联系方式！'),
            // array('tell','checkMobile','请输入正确的酒店联系方式',0,'function'),
             array('area', 'require', '请输入酒店详细地址'),
        );
        return $rules;
    }

    /**
     * 获取字节长度
     */
    public static function utf8_strlen($string = '')
    {
        preg_match_all("/./us", $string, $match);
        return count($match[0]);
    }

    protected $_validate = array(
        array('name', 'require', '酒店名不能为空!'),
        array('name', '', '名称已经存在！', 0, 'unique', 1),
        array('ht_id', 'require', '请选择酒店类型!'),
        array('provice', 'require', '请选择地区!'),
        array('area', 'require', '请填写详细地址!'),
        array('tell', 'require', '请填写酒店联系方式！'),
        //array('tell', '/^\d{3}[0-9|-]\d{4,7}$/ ', '请重新填写酒店联系方式！'),
        array('shang_name', 'require', '请填写酒店商务负责人!'),
        array('shang_tell', '/^1[3-9][0-9]\d{4,8}$/', '请重新填写酒店商务负责人联系方式!'),
        array('all_name', 'require', '请填写酒店酒店总经理!'),
        array('all_tell', '/^1[3-9][0-9]\d{4,8}$/', '请重新填写酒店酒店总经理联系方式!'),
        array('money_name', 'require', '请填写酒店酒店财务负责人!'),
        array('money_tell', '/^1[3-9][0-9]\d{4,8}$/', '请重新填写酒店酒店财务负责人联系方式!'),
        array('project_name', 'require', '请填写酒店工程负责人!'),
        array('project_tell', '/^1[3-9][0-9]\d{4,8}$/', '请重新填写酒店酒店工程负责人联系方式!'),
        array('status', 'require', '请选择酒店状态!'),
    );

    /**
     * 录入酒店
     * @param $data
     */
    public static function addHotel($data)
    {
        $data['ctime'] = time();
        $result = array('code' => 1, 'message' => '录入酒店信息失败');
        $rules = self::getAddRules();
        $HotelModel = D(self::TABLENAME_HOTEL);
        if (!$HotelModel->validate($rules)->create($data)) {
            $result['message'] = $HotelModel->getError();
            return $result;
        }
        //开始事务
        $HotelModel->startTrans();
        //file_put_contents('./Uploads/hotel.txt', "\r\n第一步",FILE_APPEND);
        $HotelData = self::operData($data);
        //file_put_contents('./Uploads/hotel.txt', "\r\n第二步",FILE_APPEND);
        // 验证酒店长度
        $namelen = self::utf8_strlen($HotelData['data']['name']);
        //file_put_contents('./Uploads/hotel.txt', "\r\n第三步",FILE_APPEND);
        if ($namelen > 19) {
            $result = array('code' => 1, 'message' => '酒店名称不能超过20个字符');
            return $result;
        }
        if ($data['hotel_user_id']) {
            if ($data['status'] == 0) {
                $result = array('code' => 1, 'message' => '未营业的酒店不能指派销售人员');
                return $result;
            }
            $HotelData['data']['is_get'] = 1;
            $HotelData['data']['get_time'] = time();
        }
        $id = $HotelModel->add($HotelData['data']);
        //file_put_contents('./Uploads/hotel.txt', "\r\n第四步",FILE_APPEND);
        if ($id) {
            // 保存酒店房间数据
            $hotel_data_room = $HotelData['data']['data_room'];
            $roomType = M('RoomType')->where(array('status' => 1))->select();
            foreach ($hotel_data_room as $key => $val) {
                if ($val) {
                    $vals['h_id'] = $id;
                    $vals['rt_id'] = $roomType[$key]['id'];
                    $vals['room_num'] = $val;
                    M('HotelRoomType')->add($vals);
                }
            }
            $info = true;
            if($HotelData['data']['status'] && $HotelData['hotel_user_id']){
                // 保存认领数据
                $Hotel['h_id'] = $id;
                $Hotel['sale_id'] = $HotelData['hotel_user_id'];
                $info = HotelGetModel::addHotelUser($Hotel);
            }
            //file_put_contents('./Uploads/hotel.txt', "\r\n第六步",FILE_APPEND);
            // 保存酒店编号
            $HotelSn = self::bulindSn($id);
            if ($info && $HotelSn) {
                $HotelModel->commit();
                //file_put_contents('./Uploads/hotel.txt', "\r\n第七步",FILE_APPEND);
                if($HotelData['data']['status'] && empty($HotelData['hotel_user_id'])){
                    //file_put_contents('./Uploads/hotel.txt', "\r\n第八步",FILE_APPEND);
                    //给销售人员推送消息
                    $user_idarr = M('User')->field('id')->where(array('role_id' => 2, 'status' => 1))->select();
                    if (empty($Hotel['sale_id']) && $data['status'] && $user_idarr) {
                        //file_put_contents('./Uploads/hotel.txt', "\r\n第九步",FILE_APPEND);
                        $oper_title = '待处理酒店认领工单';
                        $msg_content = '您有一个酒店待认领,编号：' . $HotelSn;
                        $user_ids = implode(',', i_array_column($user_idarr, 'id'));
                        $oper_url = 'ClaimHotel/index';
                        has_oper($msg_content, $user_ids, $oper_url, $oper_title);
                        //file_put_contents('./Uploads/hotel.txt', "\r\n第十步",FILE_APPEND);
                    }
                }
                //file_put_contents('./Uploads/hotel.txt', "\r\n第十一步",FILE_APPEND);
                $result['code'] = 0;
                $result['message'] = '录入酒店信息成功';
            } else {
                //file_put_contents('./Uploads/hotel.txt', "\r\n第五步",FILE_APPEND);
                $HotelModel->rollback();
            }
        }
        return $result;
    }

    /**
     * 编辑酒店
     * @param $data
     */
    public static function editHotel($id, Array $data, $HotelGetId)
    {
        $result = array('code' => 1, 'message' => '保存酒店信息失败');
        $rules = self::getEditRules();
        $HotelModel = D(self::TABLENAME_HOTEL);
        if (!$HotelModel->validate($rules)->create($data)) {
            $result['message'] = $HotelModel->getError();
            return $result;
        }
        if ($data['hotel_user_id'] && empty($data['is_sign'])) {
            $data['is_get'] = 1;
        }
        //开始事务
        $HotelModel->startTrans();
        $data['utime'] = time();
        $HotelData = self::operData($data);
        // 验证酒店长度
        $namelen = self::utf8_strlen($HotelData['data']['name']);
        if ($namelen > 19) {
            $result = array('code' => 1, 'message' => '酒店名称不能超过20个字符');
            return $result;
        }
        $m = $HotelModel->where(array('id' => $id))->save($HotelData['data']);
        if ($m) {
            // 先删除,再添加
            M('HotelRoomType')->where(array('h_id' => $id))->delete();
            $hotel_data_room = $HotelData['data']['data_room'];
            $roomType = M('RoomType')->where(array('status' => 1))->select();
            foreach ($hotel_data_room as $key => $val) {
                if ($val) {
                    $vals['h_id'] = $id;
                    $vals['rt_id'] = $roomType[$key]['id'];
                    $vals['room_num'] = $val;
                    M('HotelRoomType')->add($vals);
                }
            }
            // 保存认领数据
            $Hotel['h_id'] = $id;
            $Hotel['sale_id'] = $HotelData['hotel_user_id'];
            // 当会员数据为0的时候,清除用户信息
            if ($HotelData['hotel_user_id'] && empty($HotelData['data']['is_sign']) && $HotelData['data']['status']) {
                /*if ( == 0) {
                    $result = array('code' => 1, 'message' => '未营业的酒店不能指派销售人员');
                    return $result;
                }*/
                $info = HotelGetModel::editHotelUser($Hotel, $HotelGetId);
                if ($info) {
                    $HotelModel->commit();
                    $result['code'] = 0;
                    $result['message'] = '保存酒店信息成功';
                } else {
                    $HotelModel->rollback();
                }
            } else {
                $data = HotelGetModel::getInfos($id);
                if ($data && empty($HotelData['data']['is_sign'])) {
                    $HotelGetStatus = HotelGetModel::cancelInfo($data['sale_id'], $data['h_id']);
                    $HotelStatus = self::cancelClaim($data['h_id']);
                    if (!$HotelGetStatus || !$HotelStatus) {
                        $HotelModel->rollback();
                    } else {
                        $HotelModel->commit();
                        $result['code'] = 0;
                        $result['message'] = '保存酒店信息成功';
                    }
                } else {
                    $HotelModel->commit();
                    $result['code'] = 0;
                    $result['message'] = '保存酒店信息成功';
                }
            }

            //酒店编号
            $hsno = $HotelModel->getFieldById($id, 'sn');
            if(empty($HotelData['data']['status']) || ($HotelData['data']['status'] && $HotelData['hotel_user_id'])){
                //平台录入人员修改
                if(session('USERINFO.role_id')==8){
                    $user_id = M('User')->where(array('role_id'=>2,'status'=>1))->getField('id');
                    not_oper($hsno,$user_id);
                }else{
                    not_oper($hsno,session('USERINFO.id'));
                }
            }

            //是否之前已经分配
            $is_ditribution = M('HotelGet')->where(array('h_id' => $id, 'is_default' => 1))->getField('sale_id');
            if (empty($is_ditribution) && $HotelData['data']['status'] && empty($HotelData['hotel_user_id']) && empty($HotelData['data']['is_sign'])) {
                //给销售人员推送消息
                $user_idarr = M('User')->field('id')->where(array('role_id' => 2, 'status' => 1))->select();
                if ($user_idarr) {
                    $oper_title = '待处理酒店认领工单';
                    $msg_content = '您有一个酒店待认领,编号：' . $hsno;
                    $user_ids = implode(',', i_array_column($user_idarr, 'id'));
                    $oper_url = 'ClaimHotel/index';
                    has_oper($msg_content, $user_ids, $oper_url, $oper_title);
                }
            }
        } else {
            $HotelModel->rollback();
        }
        return $result;
    }

    /*
     * 客房类型客房数
     * */
    public function getList($id, $type = 1)
    {
        if ($type == 1) {
            $RoomType = M('RoomType')->field('id,name')->select();
            $hrt_items = D('HotelrtRoomtView')->where(array('h_id' => $id))->select();
            foreach ($RoomType as &$value) {
                foreach ($hrt_items as $hrtk => $hrtval) {
                    if ($value['id'] == $hrtval['id']) {
                        $value['room_num'] = $hrtval['room_num'];
                    }
                }
            }
            return $RoomType;
        } else {
            $RoomType = M('RoomType')->field('id,name')->select();
            $hotelRoomType = M('HotelRoomType')->where(array('h_id' => $id))->select();
            foreach ($RoomType as $value) {
                foreach ($hotelRoomType as &$hval) {
                    if ($value['id'] == $hval['rt_id']) {
                        $hval['name'] = $value['name'];
                    }
                }
            }
            return $hotelRoomType;
        }
    }

    /**
     * 获取酒店类型客房数
     */
    public static function getRoomType($id)
    {
        $where = array();
        $where['h.h_id'] = array('eq', $id);
        return M(self::TABLENAME_ROOMS)
            ->alias('l')
            ->join('left join ' . C('DB_PREFIX') . self::TABLENAME_ROOM . ' as h on h.rt_id = l.id')
            ->where($where)
            ->select();

    }

    /**
     * 操作酒店数据
     * @param $data
     */
    public static function operData($data)
    {
        $hotel_user_id = $data['hotel_user_id'];
        unset($data['hotel_user_id']);
        $listArray = array('hotel_user_id' => $hotel_user_id, 'data' => $data);
        return $listArray;
    }

    /**
     * 生成酒店ID
     * @param $id
     */
    public static function bulindSn($id)
    {
        //$sn = date('Ymd') . str_pad($id, 6, "0", STR_PAD_LEFT);
        $sn = get_reimbursement_sn();
        $where = array();
        $where['id'] = array('eq', $id);
        $result = M(self::TABLENAME_HOTEL)->where($where)->save(array('sn' => $sn));
        if ($result) {
            return $sn;
        }
        return false;
    }

    /**
     * 根据id获取酒店数据
     * @param $id
     */
    public static function findById($id)
    {
        $where = array();
        $where['l.id'] = $id;
        $where['p.is_default'] = 0;
        $data = M(self::TABLENAME_HOTEL)
            ->alias('l')
            ->join('left join ' . C('DB_PREFIX') . self::TABLENAME_TYPE . ' as h on h.id = l.ht_id')
            ->join('left join ' . C('DB_PREFIX') . HotelGetModel::TABLENAME . ' as p on p.h_id = l.id')
            ->join('left join ' . C('DB_PREFIX') . self::TABLENAME_USER . ' as s on p.sale_id = s.id')
            ->field('GROUP_CONCAT(s.real_name) as sale_name,l.*,h.name as type_name')
            ->where($where)
            ->find();
        $amp['ls.h_id'] = $id;
        $amp['ls.is_default'] = array('eq', 1);
        $amp['ls.sale_id'] = array('neq', 0);
        $data['hotel_user_name'] = M(HotelGetModel::TABLENAME)->alias('ls')
            ->join('left join ' . C('DB_PREFIX') . UserRoleModel::TABLENAME_USER . ' as ps on ps.id = ls.sale_id')
            ->where($amp)
            ->getField('ps.real_name as name');
        $data['sale_id'] = M(HotelGetModel::TABLENAME)->alias('ls')
            ->join('left join ' . C('DB_PREFIX') . UserRoleModel::TABLENAME_USER . ' as ps on ps.id = ls.sale_id')
            ->where($amp)
            ->getField('ls.sale_id');
        $data['img'] = self::getHotelImg($data['img']);
        return $data;
    }

    /**
     * 获取列表数据
     */
    public static function listData($page = 1, $pageSize = 8, $where)
    {
        $where['l.status'] = array('neq', self::STATUS_ONE);
        $data = M(self::TABLENAME_HOTEL)
            ->alias('l')
            ->join('left join ' . C('DB_PREFIX') . self::TABLENAME_TYPE . ' as p on p.id = l.ht_id')
            ->field('l.id,l.name,l.tell,l.is_get,l.is_sign,p.name as type_name,l.img,l.status,l.sn')
            ->page($page . ',' . $pageSize)
            ->where($where)
            ->order('l.id desc')
            ->select();
        foreach ($data as $key => $val) {
            $img = self::getHotelImg($val['img']);
            $data[$key]['img'] = $img[0];
        }
        return $data;
    }

    public static function listDatas($where)
    {
        $where['l.status'] = array('eq', self::STATUS_THREE);
        $data = M(self::TABLENAME_HOTEL)
            ->alias('l')
            ->join('left join ' . C('DB_PREFIX') . self::TABLENAME_TYPE . ' as p on p.id = l.ht_id')
            ->field('l.id,l.name,l.tell,l.is_get,l.is_sign,p.name as type_name,l.img,l.status,l.sn')
            ->where($where)
            ->order('l.id desc')
            ->select();
        foreach ($data as $key => $val) {
            $img = self::getHotelImg($val['img']);
            if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $img[0])) {
                $img[0] = '';
            }
            $data[$key]['img'] = $img[0];
        }
        return $data;

    }

    /**
     * 获取列表数据条数
     */
    public static function listDataCount($where)
    {
        $where['l.status'] = array('eq', self::STATUS_THREE);
        return M(self::TABLENAME_HOTEL)
            ->alias('l')
            ->join('left join ' . C('DB_PREFIX') . self::TABLENAME_TYPE . ' as p on p.id = l.ht_id')
            ->field('l.id,l.name,l.tell,l.is_get,l.is_sign,p.name as type_name,l.img,l.status,l.sn')
            ->where($where)
            ->order('l.id desc')
            ->count();
    }

    /**
     * 统计列表数据
     * @return mixed
     */
    public static function listCount($where)
    {
        $where['l.status'] = array('neq', self::STATUS_ONE);
        return M(self::TABLENAME_HOTEL)
            ->alias('l')
            ->join('left join ' . C('DB_PREFIX') . self::TABLENAME_TYPE . ' as p on p.id = l.ht_id')
            ->field('l.id,l.name,l.tell,l.is_get,l.is_sign,p.name as type_name,l.thumb_img,l.status,l.sn')
            ->order('l.ctime desc')
            ->where($where)
            ->count();
    }

    /**
     * 获取酒店列表数据
     */
    public static function getHotelData()
    {
        $where = array();
        $where['status'] = array('neq', self::STATUS_ONE);
        return M(self::TABLENAME_HOTEL)->where($where)->order('id desc')->field('id,name')->select();
    }

    /**
     * 获取星际酒店下拉框
     */
    public static function getHotelType()
    {
        return M(self::TABLENAME_TYPE)->order('sort,ctime desc')->field('id,name')->select();
    }


    /**
     * 本日营业状态统计
     */
    public static function toDayCount()
    {
        $HotelCount = array();
        $HotelCount['allHotel'] = self::getAllCountHotel();
        $HotelCount['norHotel'] = self::getNorCountHotel();
        $HotelCount['wroHotel'] = self::getWroCountHotel();
        $HotelCount['yciHotel'] = self::getYcliamCountHotel();
        $HotelCount['nciHotel'] = self::getNcliamCountHotel();

        return $HotelCount;
    }


    /**
     * 统计总酒店数(不包括被删除的酒店)
     * @return mixed
     */
    public static function getAllCountHotel()
    {
        $where = array();
        $where['status'] = array('not in', self::STATUS_ONE);
        return M(self::TABLENAME_HOTEL)->where($where)->count();
    }

    /**
     * 统计总酒店数(正常的)
     * @return mixed
     */
    public static function getAllCountHotels()
    {
        $where = array();
        $where['status'] = 1;
        return M(self::TABLENAME_HOTEL)->where($where)->count();
    }

    /**
     * 统计正常营业的酒店数
     * @return mixed
     */
    public static function getNorCountHotel()
    {
        $where = array();
        $where['status'] = array('eq', self::STATUS_THREE);
        return M(self::TABLENAME_HOTEL)->where($where)->count();
    }

    /**
     * 统计非正常营业的酒店数
     * @return mixed
     */
    public static function getWroCountHotel()
    {
        $where = array();
        $where['status'] = array('eq', self::STATUS_TWO);
        return M(self::TABLENAME_HOTEL)->where($where)->count();
    }

    /**
     * 统计已被认领的酒店数
     * @return mixed
     */
    public static function getYcliamCountHotel()
    {
        $where = array();
        $where['is_get'] = array('eq', self::STATUS_YCLAIM);
        return M(self::TABLENAME_HOTEL)->where($where)->count();
    }

    /**
     * 统计未被认领的酒店数
     * @return mixed
     */
    public static function getNcliamCountHotel()
    {
        $where = array();
        $where['is_get'] = array('eq', self::STATUS_NCLAIM);
        $where['status'] = 1;
        return M(self::TABLENAME_HOTEL)->where($where)->count();
    }


    /**
     * 统计我认领的数据
     */
    public static function getMyCount()
    {
        $listData = array();
        $listData['myycliam'] = self::getAllCountHotel();
        $listData['themycliam'] = self::getNorCountHotel();
        $listData['ncliam'] = self::getWroCountHotel();
        $listData['allhotel'] = self::getYcliamCountHotel();
        $listData['hotels'] = self::getNcliamCountHotel();
        return $listData;
    }

    /**
     * 数据
     */
    public static function getMyCounts($user_id)
    {
        $listData = array();
        $listData['myycliam'] = self::getMyYcliamCountHotel($user_id);
        $listData['themycliam'] = self::getThenYcliamCountHotels($user_id);
        $listData['ncliam'] = self::getNcliamCountHotel();
        $listData['allhotel'] = self::getAllCountHotels();
        return $listData;
    }

    /**
     * 统计我已认领的酒店数
     * @return mixed
     */
    public static function getMyYcliamCountHotel($user_id)
    {
        $where = array();
        $where['is_default'] = array('eq', 1);
        $where['sale_id'] = array('eq', $user_id);
        return M(HotelGetModel::TABLENAME)->where($where)->count();
    }

    /**
     * 别人已认领的酒店数
     * @return mixed
     */
    public static function getThenYcliamCountHotel($user_id)
    {
        $where = array();
        $where['is_default'] = array('eq', 1);
        $where['sale_id'] = array('neq', $user_id);
        return M(HotelGetModel::TABLENAME)->where($where)->count();
    }

    /**
     * 别人已认领的酒店数(正常酒店数)
     * @return mixed
     */
    public static function getThenYcliamCountHotels($user_id)
    {
        $where = array();
        $where['l.is_default'] = array('eq', 1);
        $where['l.sale_id'] = array(array('neq', 0), array('neq', $user_id), 'and');
        $where['p.status'] = 1;
        return M(HotelGetModel::TABLENAME)->alias('l')
            ->where($where)
            ->join('left join ' . C('DB_PREFIX') . self::TABLENAME_HOTEL . ' as p on p.id = l.h_id')
            ->count();

    }

    /**
     * 根据会员获取酒店名称
     * @param $user_id
     * @return mixed
     */
    public static function getHotelNames($user_id)
    {
        $where = array();
        $where['id'] = array('eq', $user_id);
        $hotel_id = M(UserRoleModel::TABLENAME_USER)->where($where)->getField('hotel_id');
        return M(HotelModel::TABLENAME_HOTEL)->where(array('id' => $hotel_id))->getField('name');
    }

    /**
     * 取消认领时间和状态
     */
    public static function cancelClaim($id)
    {
        $where = array();
        $where['id'] = $id;
        return M(self::TABLENAME_HOTEL)->where($where)->save(array('is_get' => 0, 'get_time' => ''));
    }

    /**
     * 获取有效的酒店
     */
    public static function getHotelName($provice)
    {

        if ($provice) {
            $where['l.provice'] = $provice;
        }
        $data = M(HotelModel::TABLENAME_HOTEL)->alias('l')
            ->join('left join ' . C('DB_PREFIX') . SalesModel::TABLENAME_ORDER . ' as s on s.h_id = l.id')
            ->where($where)
            ->where(array('s.type' => array('eq', 1), /*'s.status' => array('eq', 4)*/))
            ->field('l.id,l.name')->select();
        return SalesModel::listArrayKey($data, 'name');
    }

    /**
     * 根据酒店获取合同
     */
    public static function getHotelConID($id)
    {
        $result = array('code' => 1, 'message' => '该酒店下暂无合同');
        $where = array();
        $where['h_id'] = array('eq', $id);
        $m = M(SalesModel::TABLENAME_CONT)->where($where)->field('id,name')->order('ctime desc')->select();
        if ($m) {
            $result['code'] = 0;
            $result['message'] = "获取数据成功";
            $result['data'] = $m;
        }
        return $result;
    }

    /**
     * 处理酒店图片
     */
    public static function getHotelImg($img)
    {
        if ($img) {
            return explode(',', $img);
        } else {
            return '';
        }

    }

    /**
     * 每个酒店的认领有效期为30日,30日之后如果还没有提交合同,系统将此酒店设置为
     * 未认领状态,供其他人认领
     * @param $hotel_id
     */
    public static function claimHotelDate($hotel_id)
    {
        // 查看当前酒店是否认领了
        $status = M(HotelModel::TABLENAME_HOTEL)->where(array('id' => $hotel_id))->getField('is_get');
        if ($status == 1) {
            // 算出该酒店,是否在30天内没有签合同
            $data = HotelGetModel::getInfos($hotel_id);
            $ctimes = $data['ctime'];
            // 查出该酒店是否签约了合同
            $contract = M('HotelContract')->where(array('h_id' => $hotel_id))->order('ctime desc')->find();
            // 没有提交合同
            if (empty($contract)) {
                // 结束时间
                $endTime = strtotime(date('Y-m-d H:i:s', strtotime("+30day", $ctimes)));
                // 还未到30天
                $nowTime = time();
                if ($nowTime < $endTime) {
                    $nums = (round(($endTime - $nowTime) / 3600 / 24));
                    return $nums;
                } else {
                    // 清除酒店信息
                    $HotelUserModel = D(HotelModel::TABLENAME_HOTEL);
                    $HotelUserModel->startTrans();
                    $HotelGetStatus = HotelGetModel::cancelInfo($data['sale_id'], $data['h_id']);
                    $HotelStatus = self::cancelClaim($data['h_id']);
                    if ($HotelGetStatus && $HotelStatus) {
                        $HotelUserModel->commit();
                    }
                }
            }
        }
    }
}