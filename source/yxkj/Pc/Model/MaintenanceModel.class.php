<?php
/**
 * UserModel.class.php
 * @author: hjw
 * @date: 2017/9/9 10:42
 */
namespace Pc\Model;

class MaintenanceModel extends BaseModel{
    //添加维保频率的验证规则
    protected $_validate = array(
        array('num', 'require', '新增维保频率不能为空'),
        array('num', 'number', '新增维保频率只能是数字'),
    );

}