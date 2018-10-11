<?php
/**
 * UserModel.class.php
 * 用户
 * @author: wy901216
 * @date: 2017/9/4  11:42
 */
namespace Pc\Model;
use Pc\Server\PageModel;

class UserModel extends BaseModel{

    //添加酒店工作人员的验证规则
    protected $_validate = array(
        array('real_name', 'require', '用户姓名不能为空'),
        array('name', 'require', '账号不能为空'),
        array('name', '', '账号已经存在',0,'unique'),
        array('password', 'require', '密码不能为空',0,'',1),
        array('password', '/^[\S]{1,15}$/', '密码长度为1~15位',0,'',1),
        array('mobile','/^1[3-9][0-9]\d{4,8}$/','联系方式不正确'),
        array('hotel_id', 'require', '酒店名不能为空'),
        array('role_id', 'require', '用户类型不能为空'),
        //array('mobile','','联系方式已存在',0,'unique'),
    );

    public function operation(){
        $data = I('post.');
        $id = $data['id'];
        $type = $data['type'];
        $paw = $data['password'];
        $ass = explode('/',$this->data['method']);
        $this->data['module'] = $ass[0];
        //添加
        if(empty($id)){
            $salt = chr(rand(97, 122)).rand(1,9).chr(rand(97, 122)).rand(1,9);;
            $this->salt=$salt;
            $this->password = md5(md5($paw).$salt);
            if($type){
               $this->type = $type;
            }
            
            $this->data['ctime'] = time();
            $re_status = $this->add();

            //销售人员
            if($data['role_id'] == 2 && $re_status){
                $clid = M('ChannelLevel')->where(array('room_num'=>0))->getField('id');
                $sale_ext['u_id'] = $re_status;
                $sale_ext['cl_id'] = $clid;
                $sale_ext['channel_type'] = $data['channel_type'];
                M('SaleExt')->add($sale_ext);
            }
        }
        //编辑
        else{
            $this->data['utime'] = time();
            //判断是否修改密码
            if(!empty($paw)){
                $paw = I('post.password');
                $salt = chr(rand(97, 122)).rand(1,9).chr(rand(97, 122)).rand(1,9);;
                $this->salt=$salt;
                $this->password = md5(md5($paw).$salt);
            }else{
                /*$paw1= I('post.paw');
                $this->password =$paw1;*/
                unset($this->password);
            }
            $re_status = $this->save();
        }
        return $re_status;
    }



}