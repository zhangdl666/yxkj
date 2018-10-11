<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/10/26
 * Time: 14:28
 */
namespace Wx\Model;
use Wx\Model\BaseModel;
class MessageModel extends BaseModel{
    const TABLENAMELOOK = 'message_look';

    /**
	 * 对数据进行处理
	 * @param  array  $datas 	数据
	 */
	protected function _handleRows(&$datas){
		foreach ($datas['datas'] as &$value) {
			if($value['time']){
				$value['ctime'] = date('Y-m-d H:i:s',$value['time']);
			}else{
				$value['ctime'] = date('Y-m-d H:i:s',$value['ctime']);
			}
		}
	}
}