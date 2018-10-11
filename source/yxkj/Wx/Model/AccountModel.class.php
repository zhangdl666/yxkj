<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/11/2
 * Time: 16:51
 */

namespace Wx\Model;


class AccountModel extends BaseModel
{
    //添加银行卡信息的验证规则
    protected $_validate = array(
        array('name', 'require', '账户姓名不能为空'),
        array('bank_name', 'require', '所属支行不能为空'),
        array('bank_num', 'require', '银行卡号不能为空',0,'',1),
        array('bank_num','','银行卡号已存在',0,'unique'),
    );
}