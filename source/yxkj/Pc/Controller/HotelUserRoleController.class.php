<?php
/**
 * Author: ' Silent
 * Time: 2017/9/5 9:21
 */

namespace Pc\Controller;


use Pc\Model\HotelModel;
use Pc\Model\UserObjModel;
use Pc\Model\UserRoleModel;
use Pc\Server\PageModel;

class HotelUserRoleController extends BaseController
{
    public $model = 'hotel';
    private $user_id;
    private $listDataRows;

    /**
     * 初始化
     */
    public function _initialize()
    {
        // TODO: Change the autogenerated stub
        parent::_initialize();
        $this->user_id = $this->identity()->getUserId();
        $this->listDataRows = 15;
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
     * 酒店角色列表
     */
    public function index()
    {
        $p = I('get.p', 1, 'intval');
        $filter = array();
        $filter['name'] = I('get.name', '', 'trim');
        if ($filter['name']) {
            $where['l.name'] = array('like', "%" . $filter['name'] . "%");
        }
        $filter['hotel_id'] = I('get.hotel_id', -1, 'intval');
        if ($filter['hotel_id'] > -1) {
            $filter['hotel_name'] = I('get.hotel_name', '', 'trim');
            $where['l.hotel_id'] = $filter['hotel_id'];
        }
        $filter['role_id'] = I('get.role_id', -1, 'intval');
        if ($filter['role_id'] > -1) {
            $filter['role_name'] = I('get.role_name', '', 'trim');
            $where['l.role_id'] = $filter['role_id'];
        }
        $count = UserRoleModel::getUserListCount($where);
        $PcPage = new PageModel($count, $this->listDataRows);
        $data = UserRoleModel::getUserList($p, $PcPage->listRows,$where);
        $Hotel = HotelModel::getHotelData();
        $Role = UserRoleModel::getListRole();
        $this->assign('filter', $filter);
        $this->assign('role', $Role);
        $this->assign('Hotel', $Hotel);
        $this->assign('page', $PcPage->show());
        $this->assign('data', $data);
        $this->display();
    }

    /**
     * 添加用户角色
     */
    public function addUser()
    {
        if (IS_POST || IS_AJAX) {
            $data = array();
            $data['name'] = I('post.name', '', 'trim');
            $data['real_name'] = I('post.real_name', '', 'trim');
            $data['mobile'] = I('post.mobile', '', 'trim');
            $data['hotel_id'] = I('post.hotel_id');
            $data['role_id'] = I('post.role_id');
            $data['password'] = I('post.password', '', 'trim');
            $this->ajaxReturn(UserRoleModel::addRoleUser($data));
        } else {
            $Hotel = HotelModel::getHotelData();
            $Role = UserRoleModel::getListRole();
            $this->assign('role', $Role);
            $this->assign('Hotel', $Hotel);
            $this->display();
        }
    }

    /**
     * 编辑用户角色
     */
    public function editUser()
    {
        if (IS_POST || IS_AJAX) {
            $data = array();
            $data['name'] = I('post.name', '', 'trim');
            $data['real_name'] = I('post.real_name', '', 'trim');
            $data['mobile'] = I('post.mobile', '', 'trim');
            $data['hotel_id'] = I('post.hotel_id');
            $data['role_id'] = I('post.role_id');
            $data['password'] = I('post.password', '', 'trim');
            $id = I('post.id', 0, 'intval');
            $this->ajaxReturn(UserRoleModel::editRoleUser($id, $data));
        } else {
            // 查找用户信息
            $id = I('get.id', 0, 'intval');
            $Hotel = HotelModel::getHotelData();
            $Role = UserRoleModel::getListRole();
            $this->assign('role', $Role);
            $this->assign('Hotel', $Hotel);
            $data = UserRoleModel::findById($id);
            $this->assign('data', $data);
            $this->display();
        }
    }

    /**
     * 删除酒店角色
     */
    public function delUser()
    {
        $id = I('post.id', 0, 'intval');
        $this->ajaxReturn(UserRoleModel::deleteUser($id));
    }

    /**
     * 重置用户密码
     */
    public function resetPass()
    {
        if (IS_POST || IS_AJAX) {
            // 修改用户密码
            $password = I('post.password', '', 'trim');
            $id = I('post.id', 0, 'intval');
            $this->ajaxReturn(UserRoleModel::modPassword($id, $password));
        } else {
            // 查找用户信息
            $id = I('get.id', 0, 'intval');
            $data = UserRoleModel::findById($id);
            $this->assign('data', $data);
            $this->assign('id', $id);
            $this->display('modPass');
        }
    }
}