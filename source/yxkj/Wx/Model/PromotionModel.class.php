<?php
/**
 * PromotionModel.class.php
 * 后台酒店类型
 * @author 刘小伟
 * @date   2017-09-18 10:29
 */
namespace Wx\Model;
use Pc\Server\PageModel;

class PromotionModel extends BaseModel{
    protected $_validate = array(
        array('title','require','促销标题不能为空!'),
        array('title','1,20','标题长度为1~20！',3,'length'),
        array('img','require','请上传促销封面!'),
        array('content','require','正文不能为空!'),
    );
    //获取单条数据
    public  function getlister($id){
        return M('Promotion')
            ->alias('o')
            ->join('yx_hotel as h on h.id = o.h_id')
            ->where(array('o.id'=>$id))
            ->field('o.*,h.name as hotel_name')
            ->find();
    }
    /**
     * 获取数据
     * @param  array  $data
     * @return array
     */
    public function getRmList($model,$wheres=null,$order_str=null){
        $datas = $this->getDatas($model,$wheres,1,$order_str);
        session('MODEL',$model);
        session('WHERES',$wheres);
        session('ORDER',$order_str);

        //>>6.分配数据
        $re_datas = $this->limitDatas($datas,1,1);

        //对数据进行处理
        if($re_datas){
            $this->_rmHandleRows($re_datas);
        }

        //>>6.1序列化保存在session里面
        $datas = serialize($datas);
        session('DATAS', $datas);
        return $re_datas;
    }

    /**
     * ajax获取加载数据
     * @param $key 页码
     * @param $num 加载个数
     * @return array
     */
    public function showMoreDatas($key, $num = 10){
        $data = session('DATAS');
        $data = unserialize($data);
        $page = I('get.page') > 1 ? I('get.page') : 1;
        $get_datas = $this->limitDatas($data, $page, $key, $num);
        return $get_datas;
    }
    /*
     * 计算未处理数据
     * */


}