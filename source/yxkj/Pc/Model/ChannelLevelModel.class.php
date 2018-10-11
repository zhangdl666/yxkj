<?php
/**
 * Created by PhpStorm.
 * User: hjw
 * Date: 2017/9/15
 * Time: 10:02
 */

namespace Pc\Model;


class ChannelLevelModel extends BaseModel
{

//添加渠道等级验证规则
    protected $_validate = array(
        array('name', 'require', '渠道名不能为空'),
        array('room_num', 'require', '达标要求不能为空'),
        array('rate', 'require', '提成比例不能为空'),
        array('name', '', '渠道名已经存在！', 0, 'unique', 1),
        array('room_num', '', '达标要求已经存在！', 0, 'unique', 1),
        array('rate', '', '提成比例已经存在！', 0, 'unique', 1),
    );
}