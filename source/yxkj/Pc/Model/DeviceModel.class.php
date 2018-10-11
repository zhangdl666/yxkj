<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/11/2
 * Time: 10:28
 */
namespace Pc\Model;
use Pc\Server\PageModel;
class DeviceModel extends BaseModel{
    protected $_validate = array(
        array('mac','require','设备MAC不能为空!'),
        array('qr','require','二维码链接地址不能为空!'),
        array('device_id','require','设备id不能为空!'),
        array('mac','','设备MAC不能重复！',0,'unique',1),
        array('device_id','','设备id不能重复！',0,'unique',1)
    );
    /**
     * 获取列表数据
     * @param  array  $wheres 	查询条件
     * @return array
     */
    public function getResultList($wheres){
        $count = $this->where($wheres)->count();
        $listRows = C('PAGE_SIZE');
        $page = new PageModel($count, $listRows);  //自己获取请求中的页面直接使用
        $page->lastSuffix = false;
        $pageHtml = $page->show();

        $re_datas = $this->where($wheres)->limit($page->firstRow,$page->listRows)->select();
        //对数据进行处理
        $this->_handleRows($re_datas);
        return array('pageHtml'=>$pageHtml,'items'=>$re_datas);
    }
    public function addAllMac($data){
        if($this->create($data)){
            $result = $this->addAll($data);
        }else{
            $result = 0;
        }
        return $result;
    }
}