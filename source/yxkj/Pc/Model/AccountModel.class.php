<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/11
 * Time: 16:57
 */

namespace Pc\Model;


class AccountModel extends BaseModel
{
    protected $_validate = array(
        array('name','require','账号姓名不能为空!'),
        array('bank_num','/^\d{16}|\d{19}$/','卡号不正确!'),
    );
}