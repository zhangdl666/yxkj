<?php
/**
 * HotelTypeModel.class.php
 * 后台酒店类型
 * @author 刘小伟
 * @date   2017-09-06 16:29
 */
namespace Pc\Model;
use Pc\Server\PageModel;
class SaleGetmoneyModel extends BaseModel
{
    // 定义表名
    const TABLENAME_HOTEL = 'hotel';
    const TABLENAME_GETMONEY = 'sale_getmoney';
    const TABLENAME_USER = 'user';
    const TABLENAME_TYPE = 'accounts_type';

    // 定义状态枚举
    const STATUS_ONE = '1';   // 待结算
    const STATUS_TWO = '2';    // 到账确认

    //销售人员提现情况

    protected $_validate = array(
        array('rprice','require','申请金额不能为空!'),
        array('rprice','/^[0-9]+(.[0-9]{1,2})?$/','请重新填写申请金额!'),
        array('price','require','打款金额不能为空!'),
        array('price','/^[0-9]+(.[0-9]{1,2})?$/','请重新填写打款金额!'),
    );
    /**
     * 获取所有统计类型
     */
    public function counter()
    {
        $SaleGetmoney = array();
        //统计未打款次数
        $SaleGetmoney['num'] = self::getAllCountHotel(1);
        //未打款金额
        $SaleGetmoney['allMoney'] = self::MtimeMoney(1);
        //统计已打款次数
        $SaleGetmoney['timeNum'] = self::getAllCountHotel(2);
        //已打款金额
        $SaleGetmoney['MtimeMoney'] = self::MtimeMoney(2);
        //统计总单数
        $SaleGetmoney['allNum'] = ($SaleGetmoney['num'] + $SaleGetmoney['timeNum']);
        return $SaleGetmoney;
    }

    /**
     * 统计次数
     * @return mixed
     */
    public static function getAllCountHotel($id)
    {
        return M(self::TABLENAME_GETMONEY)->where("status = " . $id)->count();
    }

    /**
     * 统计金额
     * @return mixed
     */
    public static function MtimeMoney($run)
    {
        $rmoney_items = M('SaleGetmoney')->field('rprice,price,status')->where("status = " . $run)->select();
        $money = 0;
        foreach ($rmoney_items as $key => $value) {
          if($run ==1){
              $money += $value['rprice'];
          }else{
              $money += $value['price'];
          }
        }
        //金钱格式化
        $money = number_format($money, 2);
        return $money;
    }

    /*
     * 首页数据
     * 分页
     * */
    public function getResultLister($rother, $p)
    {
        if (is_numeric($rother)) {
            $where = 'o.status=' . $rother;
            $wheres = array('status' => $rother);
        } else {
            $where = '';
            $wheres = array();
        }
        $count = M('SaleGetmoney')->where($wheres)->count();
        $listRows = C('PAGE_SIZE');
        $page = new PageModel($count, $listRows);  //自己获取请求中的页面直接使用
        $page->lastSuffix = false;
        $pageHtml = $page->show();
        $data = $this
            ->alias('o')
            ->join('yx_user  as u on u.id = o.sale_id')
            ->field('o.*,u.real_name')
            ->order('o.status,o.ctime desc')
            ->where($where)
            ->page($page->firstRow = $p, $page->listRows)
            ->select();
        $this->_handleRows($data);
        return array('pageHtml' => $pageHtml, 'items' => $data);
    }

    /**
     * 打款/查看展示之前准备数据
     */
    public static function  inView()
    {
        $id = I('id');
        //试图关联查询销售人员提现情况，用户，提现账户，报销情况查询所需字段
        $data = M('SaleGetmoney')
            ->alias('o')
            ->join('yx_user  as u on u.id = o.sale_id')
            ->join('yx_account as a on a.id =o.account_id')
            ->join('yx_bank_info as b on b.id =a.bank_type')
            ->field('o.*,u.real_name,a.bank_type,a.bank_num,b.name as bank_name')
            ->where('o.id=' . $id)
            ->find();
        $data['user_priceser'] =M('Reimbursement') ->where(array('sale_id'=>$data['sale_id']))->field('price')->find();
        //计算到账金额 =申请金额-个人所得税
       // $data['user_price'] = $data['user_priceser']['price'];
       // $data['in_price'] = $data['rprice'] - $data['mprice'] ;
        //组装查看时打款凭证
        if ($data['give_img'] != '') {
            $data['arr_img'] = explode(',', $data['give_img']);
        }
        //格式化金额
       // $data['in_price'] = number_format($data['price'], 2);
        return $data;
    }
}
