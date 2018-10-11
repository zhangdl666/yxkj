<?php
/**
 * HotelTypeModel.class.php
 * 后台酒店类型
 * @author baddl
 * @date   2017-09-06 16:29
 */
namespace Pc\Model;

use Pc\Model\BaseModel;

class EquipmentOperModel extends BaseModel{

    protected $_validate = array(
        array('name','require','菜单名不能为空!'),
    );
}