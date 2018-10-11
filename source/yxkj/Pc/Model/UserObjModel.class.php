<?php
/**
 * Author: ' Silent
 * Time: 2017/9/5 15:26
 */

namespace Pc\Model;


use Think\Model;

class UserObjModel extends Model
{
    private $userKey = 'USERINFO';  // session key
    private $userId;  // 用户id
    private $roleId;  // 权限id
    private $user = array(); // 用户信息

    public function __construct()
    {
        if (empty($this->user) || !$this->userId) {
            $this->user = session($this->userKey);
            $this->userId = $this->user['id'];
            $this->roleId = $this->user['role_id'];
        }
    }

    /**
     * 返回用户id
     * @return mixed
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * 返回用户信息
     * @return array|mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * 返回权限id
     * @return mixed
     */
    public function getRoleId()
    {
        return $this->roleId;
    }
}