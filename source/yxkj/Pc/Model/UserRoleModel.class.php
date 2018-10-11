<?php
/**
 * Author: ' Silent
 * Time: 2017/9/5 9:31
 */

namespace Pc\Model;


class UserRoleModel extends BaseModel
{
    // 定义表名
    const TABLENAME_USER = 'user';
    const TABLENAME_ROLE = 'role';

    /**
     * @abstract 用户状态(-1删除 0禁用 1启用)
     */
    const STATUS_NOL = 0;
    const STATUS_BAN = 1;
    const STATUS_DEL = -1;

    /**
     * 添加验证规则
     * @return array
     */
    public static function getAddRules()
    {
        $rules = array(
            array('name', 'require', '用户帐号不能为空'),
            array('real_name', 'require', '用户姓名不能为空'),
            array('mobile', 'require', '联系方式不能为空'),
            array('hotel_id', 'require', '酒店名称不能为空'),
            array('role_id', 'require', '用户类型不能为空'),
            array('password', 'require', '密码不能为空'),
            // 验证手机号是否正确
            array('mobile','/^1(3|4|5|7|8)[0-9]\d{8}$/',"请输入正确的联系方式!",),
            // 在新增的时候验证name字段是否唯一
            array('name', '', '账号已经存在！', 0, 'unique', 1),
        );
        return $rules;
    }

    /**
     * 编辑验证规则
     * @return array
     */
    public static function geteditRules(){
        $rules = array(
            array('name', 'require', '用户账号不能为空'),
            array('real_name', 'require', '用户姓名不能为空'),
            array('mobile', 'require', '联系方式不能为空'),
            array('hotel_id', 'require', '酒店名称不能为空'),
            array('role_id', 'require', '用户类型不能为空'),
//            array('password', 'require', '密码不能为空'),
            // 验证手机号是否正确
            array('mobile','/^1(3|4|5|7|8)[0-9]\d{8}$/',"请输入正确的联系方式!",),
        );
        return $rules;
    }

    /**
     * 获取用户列表数据
     * @return mixed
     */
    public static function getUserList($page = 1, $pageSize = 8,$where)
    {
        $where['l.status'] = self::STATUS_BAN;
        return M(self::TABLENAME_USER)
                        ->alias('l')
                        ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_HOTEL . ' as p on p.id = l.hotel_id')
                        ->join('left join ' . C('DB_PREFIX') . self::TABLENAME_ROLE . ' as s on s.id = l.role_id')
                        ->field('l.name as loginname,l.real_name,l.mobile,p.name as hotel_name,s.name as role_name,l.id')
                        ->page($page . ',' . $pageSize)
                        ->where($where)
                        ->order('l.id desc')
                        ->select();
    }

    /**
     * 获取用户列表数据
     * @return mixed
     */
    public static function getUserListCount($where)
    {
        $where['l.status'] = self::STATUS_BAN;
        return M(self::TABLENAME_USER)
                        ->alias('l')
                        ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_HOTEL . ' as p on p.id = l.hotel_id')
                        ->join('left join ' . C('DB_PREFIX') . self::TABLENAME_ROLE . ' as s on s.id = l.role_id')
                        ->field('l.name as loginname,l.real_name,l.mobile,p.name as hotel_name,s.name as role_name,l.id')
                        ->where($where)
                        ->order('l.id desc')
                        ->count();
    }

    /**
     * 根据id查询用户数据
     *
     * @param $id
     * @return mixed
     */
    public static function findById($id)
    {
        $where = array();
        $where['l.id'] = $id;
        return M(self::TABLENAME_USER)
                        ->alias('l')
                        ->join('left join ' . C('DB_PREFIX') . HotelModel::TABLENAME_HOTEL . ' as p on p.id = l.hotel_id')
                        ->join('left join ' . C('DB_PREFIX') . self::TABLENAME_ROLE . ' as s on s.id = l.role_id')
                        ->where($where)
                        ->field('l.name as loginname,l.real_name,l.mobile,p.name as hotel_name,s.name as role_name,l.hotel_id,l.role_id,l.id')
                        ->find();
    }

    /**
     * 删除用户(将状态改为1,name_del)
     * @param $id
     */
    public static function deleteUser($id)
    {
        $result = array('code' => 1, 'message' => '操作失败');
        $data = array();
        $data['name'] = array('exp', "concat(name,'_del' )");
        $data['status'] = self::STATUS_DEL;
        $data['utime'] = time();
        $m = M(self::TABLENAME_USER)->where(array('id' => $id))->save($data);
        if ($m) {
            $result['code'] = 0;
            $result['message'] = '删除成功';
        }
        return $result;
    }

    /**
     * 重置用户密码
     * @param $id
     * @param $password
     */
    public static function modPassword($id, $password)
    {
        $result = array('code' => 1, 'message' => '操作失败');
        if (!$password) {
            $result['message'] = "密码不能为空";
            return $result;
        }
        $data = array();
        $data['salt'] = self::makeRandomKey();
        $data['password'] = self::makePassword($password, $data['salt']);
        $data['utime'] = time();
        if (M(self::TABLENAME_USER)->where(array('id' => $id))->setField($data)) {
            $result['code'] = 0;
            $result['message'] = '密码重置成功';
        }
        return $result;
    }

    /**
     * 新增酒店角色
     * @param array $data
     */
    public static function addRoleUser(Array $data)
    {
        $result = array('code' => 1, 'message' => '操作失败');
        $UserModel = D(self::TABLENAME_USER);
        $rules = self::getAddRules();
        if (!$UserModel->validate($rules)->create($data)) {
            $result['message'] = $UserModel->getError();
            return $result;
        }
        $data['salt'] = self::makeRandomKey();
        $data['password'] = self::makePassword($data['password'], $data['salt']);
        $data['ctime'] = time();
        if ($UserModel->add($data)) {
            $result['code'] = 0;
            $result['message'] = '添加成功';
        }
        return $result;
    }

    /**
     * 编辑用户角色
     * @param $id
     * @param array $data
     * @return array
     */
    public static function editRoleUser($id, Array $data)
    {
        $result = array('code' => 1, 'message' => '操作失败');
        $UserModel = D(self::TABLENAME_USER);
        $rules = self::geteditRules();
        if (!$UserModel->validate($rules)->create($data)) {
            $result['message'] = $UserModel->getError();
            return $result;
        }
        if ($data['password']) {
            $data['salt'] = self::makeRandomKey();
            $data['password'] = self::makePassword($data['password'], $data['salt']);
        } else {
            unset($data['password']);
        }
        $data['utime'] = time();
        if ($UserModel->where(array('id' => $id))->save($data)) {
            $result['code'] = 0;
            $result['message'] = '修改成功';
        }
        return $result;
    }

    /**
     * 获取用户角色
     */
    public static function getListRole()
    {
        return M(self::TABLENAME_ROLE)->order('id desc')->field('name,id')->select();
    }


    /**
     * 生成密码的key值
     * @return type
     */
    public static function makeRandomKey()
    {
        $colors = array();
        for ($i = 0; $i < 4; $i++) {
            $colors[] = dechex(rand(0, 15));
        }
        return implode('', $colors);
    }

    /**
     * 生成密码
     * @param type $string
     * @param type $key
     * @return type
     */
    public static function makePassword($string, $key)
    {
        return md5(md5($string) . $key);
    }

}