<?php
/**
 * Created by PhpStorm.
 * User: hjw
 * Date: 2017/9/19
 * Time: 10:51
 */

namespace Wx\Model;
use Pc\Server\PageModel;

class HotelUserModel extends BaseModel
{

    //添加酒店工作人员的验证规则
    protected $_validate = array(
        array('real_name', 'require', '用户姓名不能为空'),
        array('name', 'require', '账号不能为空'),
        array('password', 'require', '密码不能为空',0,'',1),
        array('mobile','/^1[3-9][0-9]{9}$/','联系方式不正确',0,'unique'),
    );
}