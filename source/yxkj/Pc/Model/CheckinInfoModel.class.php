<?php
/**
 * CheckinInfoModel.class.php
 * 用户使用历史
 * @author 刘小伟
 * @date   2017-09-16 16:29
 */
namespace Pc\Model;
use Pc\Server\PageModel;

class CheckinInfoModel extends BaseModel{

	/**
	 * 获取列表数据
	 */
    public function hostory($p){
        $count =M('CheckinInfo') ->alias('o')
            ->join('yx_user  as u on u.id = o.u_id')
            ->field('o.*,u.name,u.uuid,sum(o.eoper_num) as eoper_num')
            ->count("distinct o.u_id");
            
        $listRows =C('PAGE_SIZE');
        $page = new PageModel($count, $listRows);  //自己获取请求中的页面直接使用
        $page->lastSuffix = false;
        $pageHtml = $page->show();
        $data = M('CheckinInfo')
            ->alias('o')
            ->join('yx_user  as u on u.id = o.u_id')
            ->field('o.*,u.name,u.uuid,sum(o.eoper_num) as eoper_num')
            ->group('o.u_id')
            ->page($page->firstRow=$p,$page->listRows)
            ->select();
        $this->_handleRows($data);
        return array('pageHtml'=>$pageHtml,'items'=>$data);
    }

    //历史记录
    public function getmore($id,$p){
        $count =M('CheckinInfo') ->where(array('u_id' =>$id))->count();
        $listRows =20;
        $page = new PageModel($count, $listRows);  //自己获取请求中的页面直接使用
        $page->lastSuffix = false;
        $pageHtml = $page->show();
        $data = M('CheckinInfo')
            ->alias('o')
            ->join('yx_user  as u on u.id = o.u_id')
            ->join('yx_hotel  as h on h.id = o.h_id')
            ->join('yx_room_type  as r on r.id = o.rt_id')
            ->field('o.*,u.name,u.uuid,h.name as hotel_name,r.name as type_name,h.provice,h.city,h.county,h.area')
            ->where(array('u_id' =>$id))
            ->order('o.ctime desc')
            ->page($page->firstRow=$p,$page->listRows)
            ->select();
        $this->_handleRows($data);
        return array('pageHtml'=>$pageHtml,'items'=>$data);
    }
}