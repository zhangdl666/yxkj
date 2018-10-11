<?php
/**
 * Created by PhpStorm.
 * User: hjw
 * Date: 2017/9/9
 * Time: 14:27
 */

namespace Pc\Model;


class AccountsTypeModel extends BaseModel
{
    // 自动验证定义
    protected $_validate = array(
        array('type','require','结算模式不能为空!'),
        //array('price','require','结算价格不能为空!'),
        //array('price','number','结算价格只能是数字!'),
        array('remark','require','请填写结算模式说明!'),
    );
}