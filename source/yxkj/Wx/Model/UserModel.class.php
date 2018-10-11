<?php
/**
 * UserModel.class.php
 * 后台 用户模型
 * @author baddl
 * @date   2017-09-18 14:11
 */
namespace Wx\Model;

class UserModel extends BaseModel{
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
    /**
     * 打款/查看展示之前准备数据
     */

    protected $_validate = array(
        array('name', 'require', '用户账号不能为空'),
        array('name', '', '用户账号已经存在',0,'unique',1),
        array('real_name', 'require', '用户姓名不能为空'),
        array('password', 'require', '密码不能为空',0,'',1),
        array('password', '/^[a-zA-Z\d_]{1,15}$/', '密码长度为1~15位',0,'',1),
        array('mobile', 'require', '联系方式不能为空',0,'',1),
        array('mobile','/^1[3-9][0-9]\d{4,8}$/','联系方式不正确'),
        array('hotel_id', 'require', '酒店名不能为空'),
        array('role_id', 'require', '用户类型不能为空'),
    );
}