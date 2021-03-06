<?php
/**
 * Author: ' Silent
 * Time: 2017/10/8 20:33
 */

namespace Wx\Controller;
use Wx\Model\UserObjModel;


class PlatformIndexController extends BaseController
{
    private $user_id;

    public function _initialize()
    {
        // TODO: Change the autogenerated stub
        $this->user_id = $this->identity()->getUserId();
    }

    /**
     * 获取登录用户对象
     * @return type
     */
    public function identity()
    {
        if (!$this->userObj) {
            $this->userObj = new UserObjModel();
        }
        return $this->userObj;
    }

    /**
     * 首页
     */
    public function index(){
        // 收益

        // 认领
        $hotel=M('hotel')->alias('h')
                 ->join('yx_hotel_get as g ON g.h_id=h.id')
                 ->where(['g.sale_id'=>$this->user_id,'is_default'=>0])->count();
        // 合同

        // 报销
        $rst=M('reimbursement')->where(['sale_id'=>$this->user_id,'status'=>1])->count();
        // 结算
        $rmy=M('return_money')->where(['status'=>1])->count();
        // 酒店角色
        // 代办
        $daiban = $this->getNoHaddleNum();


        $this->assign('daiban',$daiban);
        $this->assign('hotel',$hotel);
        $this->assign('rst',$rst);
        $this->assign('rmy',$rmy);
        $this->display();
    }
}