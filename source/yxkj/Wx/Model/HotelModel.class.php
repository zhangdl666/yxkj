<?php
/**
 * Created by PhpStorm.
 * User: hjw
 * Date: 2017/9/21
 * Time: 13:49
 */

namespace Wx\Model;


class HotelModel extends BaseModel
{
    protected $_validate = array(
        array('name','require','酒店名不能为空!'),
        array('name','','酒店名已存在',0,'unique'),
        array('room_num','number','客房数量不正确'),
        //array('room_num',0,'客房数量不能为0',0,'notequal'),
        array('provice','require','地址不能为空!'),
        array('area','require','详细地址不能为空!'),
        array('tell','require', '联系方式不能为空'),
        //array('tell', '/^\d{3}[0-9|-]\d{4,7}$/ ', '请重新填写酒店联系方式！'),
        //  array('tell','checkMobile','请输入正确的酒店联系方式',0,'function'),
        //array('tell','/^1[3|4|5|7|8][0-9]\d{4,8}$/','联系方式不合法!'),
////        array('img','require','酒店图片不能为空!'),
//        array('shang_name','require','商务负责人不能为空!'),
//        array('shang_tell','/^1[3|4|5|7|8][0-9]\d{4,8}$/','商务负责人联系方式不合法!'),
//        array('all_name','require','总经理不能为空!'),
//        array('all_tell','/^1[3|4|5|7|8][0-9]\d{4,8}$/','总经理联系方式不合法!'),
//        array('money_name','require','财务负责人不能为空!'),
//        array('money_tell','/^1[3|4|5|7|8][0-9]\d{4,8}$/','财务负责人联系方式不合法!'),
//        array('project_name','require','工程负责人不能为空!'),
//        array('project_tell','/^1[3|4|5|7|8][0-9]\d{4,8}$/','工程负责人联系方式不合法!'),
//        array('status','require','酒店类型名不能为空!'),
    );
}