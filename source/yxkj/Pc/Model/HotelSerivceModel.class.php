<?php
/**
 * Created by PhpStorm.
 * User: hjw
 * Date: 2017/9/9
 * Time: 13:57
 */

namespace Pc\Model;


class HotelSerivceModel extends BaseModel
{
    protected $_validate = array(
        array('name','require','服务项目不能为空'),
        array('name', '', '服务项目名已经存在！', 0, 'unique', 1),
        array('img','require','图标不能为空')
    );
}