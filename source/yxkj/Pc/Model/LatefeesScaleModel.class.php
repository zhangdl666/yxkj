<?php
/**
 * Created by PhpStorm.
 * User: hjw
 * Date: 2017/9/15
 * Time: 9:38
 */

namespace Pc\Model;


class LatefeesScaleModel extends BaseModel
{

    //添加滞纳金比例验证规则
    protected $_validate = array(
        array('name', 'require', '滞纳金比例不能为空'),
        array('name', '', '滞纳金比例已经存在！', 0, 'unique', 1)
    );
}