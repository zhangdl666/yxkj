<?php
/**
 * PromotionModel.class.php
 * 后台酒店类型
 * @author 刘小伟
 * @date   2017-09-18 10:29
 */
namespace Pc\Model;
use Pc\Server\PageModel;

class PromotionModel extends BaseModel{
    protected $_validate = array(
        array('title','require','促销标题不能为空!'),
        array('title','1,20','标题长度为1~20！',3,'length'),
        array('img','require','请上传促销封面!'),
        array('content','require','正文不能为空!'),
    );
    //首页数据查询
    public function getall($p){
        $count =$this ->where(array('h_id'=>$_SESSION['USERINFO']['hotel_id'])) ->count();
        $listRows = C('PAGE_SIZE');
        $page = new PageModel($count, $listRows);  //自己获取请求中的页面直接使用
        $page->lastSuffix = false;
        $pageHtml = $page->show();
        $data =$this
            ->alias('o')
            ->join('yx_hotel as h on h.id = o.h_id')
            ->field('o.*,h.name as hotel_name')
            ->order('o.ctime desc,o.utime desc')
            ->where(array('h_id'=>$_SESSION['USERINFO']['hotel_id']))
            ->page($page->firstRow=$p,$page->listRows)
            ->select();
        $this->_handleRows($data);
        return array('pageHtml'=>$pageHtml,'items'=>$data);
    }
    //查看编辑页面数据
    public  function getlist($id){
        $data = $this
            ->alias('o')
            ->join('yx_hotel as h on h.id = o.h_id')
            ->where(array('o.id'=>$id))
            ->field('o.*,h.name as hotel_name')
            ->find();
      return $data;
    }


}