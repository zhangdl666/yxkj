<?php
/**
 * Author: ' Silent
 * Time: 2017/9/5 15:53
 */

namespace Pc\Controller;
use Pc\Model\HotelGetModel;
use Pc\Model\HotelModel;
use Pc\Model\HotelUserModel;
use Pc\Model\UserObjModel;
use Pc\Model\UserRoleModel;
use Pc\Server\PageModel;
use Think\Think;

class ClaimHotelController extends BaseController
{
    private $user_id;
    private $user_data;
    private $listDataRows;
    public $model = 'Hotel';

    /**
     * 初始化
     */
    public function _initialize()
    {
        parent::_initialize();
        $this->user_id = $this->identity()->getUserId();
        // 判断当前是否是平台销售人员
        $this->user_data = $this->identity()->getUser();
        if ($this->user_data['role_id'] == 2) {
            $this->assign('role_id', '1');
        }
        $this->listDataRows = C('PAGE_SIZE');
    }


    /**
     * 获取登录用户对象
     * @return type
     */
    public function identity()
    {
        if (!$this->userObj) {
            $this->userObj = new UserObjModel();
        }
        return $this->userObj;
    }

    /**
     * 列表数据
     */
    public function index()
    {
        $p = I('get.p', 1, 'intval');
        $filter = array();
        $wheres=array();
        $filter['name'] = I('get.name', '', 'trim');
        if ($filter['name']) {
            $where['l.name'] = array('like', "%" . $filter['name'] . "%");
        }
        $filter['is_get'] = I('get.is_get');
        $filter['is_name'] = I('get.is_name', '', 'trim');
        if ($filter['is_get'] > -1 && $filter['is_get'] != '') {
            $filter['is_name'] = I('get.is_name', '', 'trim');
            $where['l.is_get'] = array('eq', $filter['is_get']);
        }
        $filter['ht_id'] = I('get.ht_id', -1, 'intval');
        if ($filter['ht_id'] > -1 && $filter['ht_id'] != 0) {
            $filter['type_name'] = I('get.type_name', '', 'trim');
            $where['l.ht_id'] = $filter['ht_id'];
            $wheres['l.ht_id'] = $filter['ht_id'];
        }
        // 如果当前是平台销售经理
        if($this->user_data['role_id'] == 3){
            // 只有查看页面,状态只有已认领和未认领
            // 展示销售经理下面的销售人员情况
            // 2017-11-7 和平台销售人员一致

//            $UserData = M(UserRoleModel::TABLENAME_USER)->where(array('parent_id'=>$this->user_id))->field('id')->select();
//            $result = array_reduce($UserData, function ($result, $value) {
//                return array_merge($result, array_values($value));
//            }, array());
            $countData = HotelModel::getMyCount($this->user_id);
//            $data = HotelModel::listDatas($where);
//            foreach ($data as $key => $val) {
//                $data[$key]['hotel_status'] = HotelUserModel::checkUserStatus($this->user_id, $val['id']);
//                $sale_id = M(HotelModel::TABLENAME_GET)->where(array('h_id'=>$val['id'],'is_default'=>1))->getField('sale_id');
//                if(!in_array($sale_id,$result)){
//                    unset($data[$key]);
//                }
//            }
//            $count = count($data);//得到数组元素个数
//            $Page = new PageModel($count, 7);// 实例化分页类 传入总记录数和每页显示的记录数
//            $data = array_slice($data, $Page->firstRow, $Page->listRows);
//            $show = $Page->show();// 分页显示输出
//            $this->assign('page', $show);
//            $this->assign('countData', $countData);
//            $countData = HotelModel::getMyCounts($this->user_id);
            $data = HotelModel::listDatas($where);
            foreach ($data as $key => $val) {
                $data[$key]['hotel_status'] = HotelUserModel::checkUserStatus($this->user_id, $val['id']);
                if ($filter['is_get'] == 1) {
                    if ($data[$key]['hotel_status'] == HotelUserModel::STATUS_ONE) {
                        unset($data[$key]);
                    }
                }
            }
            $count = count($data);//得到数组元素个数
            $Page = new PageModel($count, 7);// 实例化分页类 传入总记录数和每页显示的记录数
            $data = array_slice($data, $Page->firstRow, $Page->listRows);
            $show = $Page->show();// 分页显示输出
            $this->assign('page', $show);
            $this->assign('countData', $countData);
            $HotelType = HotelModel::getHotelType();
            $this->assign('filter', $filter);
            $this->assign('HotelType', $HotelType);
            $this->assign('data', $data);
            $this->display('pindex');
        }else{
            // 我的认领
            if ($filter['is_get'] == 2) {

                $data = HotelModel::listDatas($wheres);
                $countData = HotelModel::getMyCounts($this->user_id);
                foreach ($data as $key => $val) {
                    $data[$key]['hotel_status'] = HotelUserModel::checkUserStatus($this->user_id, $val['id']);
                    if ($data[$key]['hotel_status'] != HotelUserModel::STATUS_ONE) {
                        unset($data[$key]);
                    }
                }
                $count = count($data);//得到数组元素个数
                $Page = new PageModel($count, C('PAGE_SIZE'));// 实例化分页类 传入总记录数和每页显示的记录数
                $data = array_slice($data, $Page->firstRow, $Page->listRows);
                $show = $Page->show();// 分页显示输出

                $this->assign('page', $show);
                $this->assign('countData', $countData);
            } else {
                $countData = HotelModel::getMyCounts($this->user_id);
                $data = HotelModel::listDatas($where);
                foreach ($data as $key => $val) {
                    $data[$key]['hotel_status'] = HotelUserModel::checkUserStatus($this->user_id, $val['id']);
                    if ($filter['is_get'] == 1) {
                        if ($data[$key]['hotel_status'] == HotelUserModel::STATUS_ONE) {
                            unset($data[$key]);
                        }
                    }
                }
                $count = count($data);//得到数组元素个数
                $Page = new PageModel($count, C('PAGE_SIZE'));// 实例化分页类 传入总记录数和每页显示的记录数
                $data = array_slice($data, $Page->firstRow, $Page->listRows);
                $show = $Page->show();// 分页显示输出
                $this->assign('page', $show);
                $this->assign('countData', $countData);
            }

            $this->assign('first',$Page->firstRow);
            $HotelType = HotelModel::getHotelType();
            $this->assign('filter', $filter);
            $this->assign('HotelType', $HotelType);
            $this->assign('data', $data);
            $this->display();
        }

    }

    /**
     * 我的认领列表
     */
    public function getMyList()
    {
        $p = I('get.p', 1, 'intval');
        $filter = array();
        $filter['name'] = I('get.name', '', 'trim');
        if ($filter['name']) {
            $where['p.name'] = array('like', "%" . $filter['name'] . "%");
        }
        $filter['is_get'] = I('get.is_get', -1, 'intval');
        if ($filter['is_get'] > -1) {
            $filter['is_name'] = I('get.is_name', '', 'trim');
            $where['p.is_get'] = $filter['is_get'];
        }
        $filter['ht_id'] = I('get.ht_id', -1, 'intval');
        if ($filter['ht_id'] > -1 && $filter['ht_id'] != 0) {
            $filter['type_name'] = I('get.type_name', '', 'trim');
            $where['p.ht_id'] = $filter['ht_id'];
        }
        $countData = HotelModel::getMyCount($this->user_id);
        $count = HotelUserModel::getMyListHotelCount($where, $this->user_id);
        $PcPage = new PageModel($count, $this->listDataRows);
        $data = HotelUserModel::getMyListHotel($p, $PcPage->listRows, $where, $this->user_id);
        foreach ($data as $key => $val) {
            $data[$key]['hotel_status'] = HotelUserModel::checkUserStatus($this->user_id, $val['id']);
        }
        $HotelType = HotelModel::getHotelType();
        $this->assign('filter', $filter);
        $this->assign('HotelType', $HotelType);
        $this->assign('page', $PcPage->show());
        $this->assign('countData', $countData);
        $this->assign('data', $data);
        $this->display();
    }

    /**
     * 查看
     */
    public function seeHotel()
    {
        $id = I('get.id', 0, 'intval');
        $data = HotelModel::findById($id);
        if(!empty($data['img'])){
            for($i=0; $i<count($data['img']) ;$i++){
                if(!file_exists($_SERVER['DOCUMENT_ROOT'].$data['img'][$i])){
                    $data['img'][$i] = '';
                }
            }
        }
        $date = HotelModel::claimHotelDate($id);
        // 检测酒店当前状态
        $status = HotelUserModel::checkUserStatus($this->user_id, $id);
        $RoomType = HotelModel::getList($id,$type=2);
        $this->assign('RoomType', $RoomType);
        $this->assign('status', $status);
        $this->assign('id', $id);
        $this->assign('data', $data);
        $this->assign('date',$date);
        $this->display();
    }

    /**
     * 编辑
     */
    public function logged()
    {
        $id = I('get.id', 0, 'intval');
        $HotelType = HotelModel::getHotelType();
//        $RoomType = HotelModel::getRoomType($id);
        $RoomType = HotelModel::getList($id);
        $date = HotelModel::claimHotelDate($id);
        $this->assign('date',$date);
        $Hotel = HotelModel::findById($id);
        $HotelGteId = HotelGetModel::getId($Hotel['sale_id'], $id);
        $this->assign('id', $id);
        $this->assign('HotelGetId', $HotelGteId);
        $this->assign('HotelType', $HotelType);
        $this->assign('RoomType', $RoomType);
        $this->assign('Hotel', $Hotel);
        $this->display();
    }

    /**
     * 认领酒店
     */
    public function userClaim()
    {
        $id = I('post.id', 0, 'intval');
        $info = HotelUserModel::getUserClaim($id, $this->user_id);

        if($info){
            //酒店编号
            $hsno = $this->model->getFieldById($id,'sn');
            not_oper($hsno,session('USERINFO.id'));  
        }

        $this->ajaxReturn($info);
    }

    /**
     * 取消认领
     */
    public function userNoClaim()
    {
        $id = I('post.id', 0, 'intval');
        $info = HotelUserModel::getNoUserClaim($id, $this->user_id);

        //酒店编号
        $hsno = $this->model->getFieldById($id,'sn');
        not_oper($hsno,session('USERINFO.id'));

        //给销售人员推送消息
        $user_idarr = M('User')->field('id')->where(array('role_id'=>2,'status'=>1))->select();
        if($user_idarr){
            $oper_title = '待处理酒店认领工单';
            $msg_content= '您有一个酒店待认领,编号：'.$hsno;
            $user_ids = implode(',', i_array_column($user_idarr,'id'));
            $oper_url = 'ClaimHotel/index';
            has_oper($msg_content,$user_ids,$oper_url,$oper_title);
        }

        $this->ajaxReturn($info);
    }

}