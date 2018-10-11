<?php
/**
 * Created by ${CONTROLLER_NAME}.
 * @auther: 刘小伟
 * Date: 2017/9/20
 * Time: 17:07
 */
namespace Pc\Model;
use Pc\Server\PageModel;

class MessageModel extends BaseModel{
    // 自动验证定义
    //系统消息查看记录表
    const TABLENAMELOOK = 'message_look';
    protected $_validate = array(
        array('title','require','标题不能为空!'),
        array('title','1,20','标题长度不符！',3,'length'),
        array('type','require','请选择消息类型!'),
        array('type', '系统消息', '选择类型不正确',0,'equal'),
        //array('get_ids','require','请选择接收人员!'),
        array('content','require','请输入消息内容!'),
        array('title','1,500','内容长度不符！',3,'length'),
    );    
}