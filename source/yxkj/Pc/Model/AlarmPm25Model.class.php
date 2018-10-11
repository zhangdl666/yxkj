<?php
/**
 * Created by PhpStorm.
 * User: rzhd
 * Date: 2018/9/4
 * Time: 13:40
 */

namespace Pc\Model;


class AlarmPm25Model extends BaseModel{
    protected $_validate = array(
        array('start','require','账号姓名不能为空!'),
        array('end','require','告警PM2.5开始值不能为空'),
        array('time','require','告警PM2.5结束值不能为空!'),
    );
}