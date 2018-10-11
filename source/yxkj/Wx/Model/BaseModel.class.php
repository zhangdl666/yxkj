<?php
/**
 * BaseModel.class.php
 * 后台公共模型
 * @author baddl
 * @date   2017-09-04 09:54
 */
namespace Wx\Model;
use Think\Model;
use Think\Page;

class BaseModel extends Model{
    /**
     * 获取数据
     * @param  array  $data
     * @return array
     */
    public function getList($model,$wheres=null,$order_str=null){
        $datas = $this->getDatas($model,$wheres,1,$order_str);
        session('MODEL',$model);
        session('WHERES',$wheres);
        session('ORDER',$order_str);

        //>>6.分配数据
        $re_datas = $this->limitDatas($datas,1,1);

        //对数据进行处理
        if($re_datas){
            $this->_handleRows($re_datas);
        }

        //>>6.1序列化保存在session里面
        $datas = serialize($datas);
        session('DATAS', $datas);
        return $re_datas;
    }

	/**
     * 获取数据根据相关条件
     * @param  string   $model      模型
     * @param  array    $wheres     条件
     * @param  int      $page       当前页
     * @param  string   $order_str  排序方式
     * @return array
     */
    public function getDatas($model,$wheres,$page,$order_str){
        $start_limit = ($page-1)*C('WPAGE_SIZE');
        $re_datas = D($model)->where($wheres)->order($order_str)->limit($start_limit.','.C('WPAGE_SIZE'))->select();
        return $re_datas;
    }

    /**
     * 加载数据(类似分页的方法)
     * @param $data         所有数据
     * @param int $page     加载数据页码
     * @param int $key      显示数据页码
     * @param int $num      显示个数
     * @return array
     */
    public function limitDatas($data,$page,$key,$num = 10){
        $show_data = array();
        //>>1.数组的总条数
        $count = count($data);
        //>>2.开始取值的索引值
        $index_start = ($key - 1) * $num;
        //>>3.根据$num循环取值的个数
        for ($i = 0; $i <= ($num - 1); ++$i) {
            if ($index_start <= ($count - 1)) {
                $show_data[] = $data[$index_start];
                ++$index_start;
            } else {
                break;//跳出循环
            }
        }

        //如果之前查询的记录加载完
        if(empty($show_data)){
            $page = ($page*1+1);
            $datas =  $this->getDatas(session('MODEL'),session('WHERES'),$page,session('ORDER'));
            //数据已经查询完
            if(empty($datas)){
                return null;
            }

            $seller_list = $this->limitDatas($datas,$page,1);

            //>>6.1序列化保存在session里面
            $datas = serialize($datas);
            session('DATAS', $datas);
            return $seller_list;
        }

        return array('page'=>$page,'key'=>$key,'datas'=>$show_data);
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
        //对数据进行处理
        if($get_datas){
            $this->_handleRows($get_datas);
        }
        return $get_datas;
    }

	/**
	 * 查询数据具体信息
	 * @param  int  $id 	查询单条数据信息的ID号
	 * @return array
	 */
	public function getInfo($id){
		$re_data = $this->where(array('id'=>$id))->find();
		return $re_data;
	}

	/**
	 * 对添加/编辑数据进行操作
	 */
	public function operation(){
		$id = I('post.id');
		//添加
		if(empty($id)){
			$this->data['ctime'] = time();
			$re_status = $this->add(); 
		}
		//编辑
		else{
            $is_exsits = $this->where(I('post.'))->getField('id');
            if($is_exsits){
                return true;
            }
			$re_status = $this->save();
		}

		return $re_status;
	}

	/**
	 * 改变状态
	 * @param  int  $ids 		数据IDS
	 * @param  string $status 	状态值
	 * @return bool
	 */
	public function changeStatus($ids,$status='-1'){
		$wheres['id'] = array('in',$ids);
		$data['status'] = $status;
		$re_boolean = $this->where($wheres)->save($data);

		if($re_boolean){
			return true;
		}else{
			return false;
		}
	}

    /**
     * 删除数据
     * @param  string  $ids     数据IDS
     * @return bool
     */
    public function delete_data($ids){
        $wheres['id'] = array('in',$ids);
        $re_boolean = $this->where($wheres)->delete();

        if($re_boolean){
            return true;
        }else{
            return false;
        }
    }

	/**
	 * 对数据进行处理
	 * @param  array  $datas 	数据
	 */
	protected function _handleRows(&$datas){

	}

	/**
      * 导出Excle
      * @param string $field    数组对象字段
      * @param string $column   字段
      * @param array  $arr      数组对象
      * @param string $headtitle文件标题
      * @param string $title    总说明
      */
     public function excelExport($field,$arr,$column=null,$headtitle=null,$title=null){
        Vendor('PHPexcel.PHPExcel');
        $objPHPExcel = new \PHPExcel();    
        $objProps = $objPHPExcel->getProperties();
        $objPHPExcel->setActiveSheetIndex(0);     
        $objActSheet = $objPHPExcel->getActiveSheet(); 
           
        $objActSheet->setTitle('Sheet1');
        
        $ascii = 65;
        $i = 1;
        $contacts_column_arr = explode(',', $column);
        //总说明
        if($title){
            $arr_count = count($contacts_column_arr);
            $tableColumn_len = $ascii+$arr_count-1;
            if($tableColumn_len > 90){
                $objActSheet->mergeCells(chr($ascii).$i.':'.chr(90).$i);
            }else{
                $objActSheet->mergeCells(chr($ascii).$i.':'.chr($tableColumn_len).$i);
            }
            $objActSheet->setCellValue(chr($ascii).$i, $title);
            $i++;
        }

        //每一列Title
        if($column){
            $cv = '';
            foreach($contacts_column_arr as $val){
                $objActSheet->setCellValue($cv.chr($ascii).$i, $val);
                $ascii++;
                //双前缀时用
                if($ascii == 91){
                    $ascii = 65;
                    $cv .= chr(strlen($cv)+65);
                }
            }
            $i++;
        }
        
        $ascii = 65;
        $cv = '';
        $contacts_fields_arr = explode(',', $field);
        foreach($arr as $val){
            //设置行高
            $objActSheet->getRowDimension($i)->setRowHeight(40); 
            foreach($contacts_fields_arr as $valu){
                //是图片
                if($valu == 'logo' || $valu == 'min_logo'){
                    //设置列宽
                    $objActSheet->getColumnDimension($cv.chr($ascii))->setAutoSize(true);
                    //水平垂直居中
                    /*$objActSheet->getStyle($cv.chr($ascii).$i)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
                    $objActSheet->getStyle($cv.chr($ascii).$i)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER); */
                    
                    $objDrawing = new \PHPExcel_Worksheet_Drawing();
                    $objDrawing->setPath(getcwd().$val[$valu]);  
                    $objDrawing->setCoordinates($cv.chr($ascii).$i);
                    //距X坐标
                    $objDrawing->setOffsetX(9);
                    $objDrawing->setOffsetY(9);
                    /*//旋转角度
                    $objDrawing->setRotation(25);*/

                    $objDrawing->setWidth(35);
                    $objDrawing->setHeight(35);
                    $objDrawing->setWorksheet($objActSheet);

                }
                //防止使用科学计数法，在数据前加空格
                elseif(preg_match('/^[0-9]+$/', $val[$valu])){
                    $objActSheet->setCellValue($cv.chr($ascii).$i, ' '.$val[$valu]);
                }else{
                    $objActSheet->setCellValue($cv.chr($ascii).$i, $val[$valu]);
                }


                //双前缀时用
                $ascii++;
                if($ascii == 91){
                    $ascii = 65;
                    $cv .= chr(strlen($cv)+65);
                }
            }
            $ascii = 65;
            $cv = '';
            $i++;
        }
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        ob_end_clean();
        header("Content-Type: application/vnd.ms-excel;");
        header("Content-Disposition:attachment;filename=".($headtitle ? $headtitle : date('YmdHis',mktime())).".xls");
        header("Pragma:no-cache");
        header("Expires:0");
        $objWriter->save('php://output'); 
    }

    /**
     * 根据ip转换地址
     * @param $ip
     * @param $newIp
     * @return string
     */
    function getAddressByIp($ip,$newIp=null){
        if(!isset($newIp)){
            $newIp = new \Org\Util\IP();
        }
        if($ip=='127.0.0.1'){
            $ads = "本机地址";
        }else{
            $tmp = $newIp->find($ip);
            if($tmp[1]==$tmp[2]){
                $ads = $tmp[1];
            }elseif($tmp[3]==''){
                $ads = $tmp[1].'省'.$tmp[2].'市';
            }else{
                $ads = $tmp[1].'省'.$tmp[2].'市'.$tmp[3];
            }
        }
        return $ads;
    }

    /**
     * 获取当前电脑或手机操作系统
     * @return string
     */
    function getOS() {
        $os = '';
        $Agent = $_SERVER['HTTP_USER_AGENT'];
        if (eregi('win', $Agent) && strpos($Agent, '95')) {
            $os = 'Win 95';
        } elseif (eregi('win 9x', $Agent) && strpos($Agent, '4.90')) {
            $os = 'Win ME';
        } elseif (eregi('win', $Agent) && ereg('98', $Agent)) {
            $os = 'Win 98';
        } elseif (eregi('win', $Agent) && eregi('nt 5.0', $Agent)) {
            $os = 'Win 2000';
        } elseif (eregi('win', $Agent) && eregi('nt 6.0', $Agent)) {
            $os = 'Win Vista';
        } elseif (eregi('win', $Agent) && eregi('nt 6.1', $Agent)) {
            $os = 'Win 7';
        } elseif (eregi('win', $Agent) && eregi('nt 5.1', $Agent)) {
            $os = 'Win XP';
        } elseif (eregi('win', $Agent) && eregi('nt 6.2', $Agent)) {
            $os = 'Win 8';
        } elseif (eregi('win', $Agent) && eregi('nt 6.3', $Agent)) {
            $os = 'Win 8.1';
        } elseif (eregi('win', $Agent) && eregi('nt 10', $Agent)) {
            $os = 'Win 10';
        } elseif (eregi('win', $Agent) && eregi('nt', $Agent)) {
            $os = 'Win NT';
        } elseif (eregi('win', $Agent) && ereg('32', $Agent)) {
            $os = 'Win 32';
        } elseif (ereg('Mi', $Agent)) {
            $os = '小米';
        } elseif (eregi('Android', $Agent) && ereg('LG', $Agent)) {
            $os = 'LG';
        } elseif (eregi('Android', $Agent) && ereg('M1', $Agent)) {
            $os = '魅族';
        } elseif (eregi('Android', $Agent) && ereg('MX4', $Agent)) {
            $os = '魅族4';
        } elseif (eregi('Android', $Agent) && ereg('M3', $Agent)) {
            $os = '魅族';
        } elseif (eregi('Android', $Agent) && ereg('M4', $Agent)) {
            $os = '魅族';
        } elseif (eregi('Android', $Agent) && ereg('Huawei', $Agent)) {
            $os = '华为';
        } elseif (eregi('Android', $Agent) && ereg('HM201', $Agent)) {
            $os = '红米';
        } elseif (eregi('Android', $Agent) && ereg('KOT', $Agent)) {
            $os = '红米4G版';
        } elseif (eregi('Android', $Agent) && ereg('NX5', $Agent)) {
            $os = '努比亚';
        } elseif (eregi('Android', $Agent) && ereg('vivo', $Agent)) {
            $os = 'Vivo';
        } elseif (eregi('Android', $Agent)) {
            $os = 'Android';
        } elseif (eregi('linux', $Agent)) {
            $os = 'Linux';
        } elseif (eregi('unix', $Agent)) {
            $os = 'Unix';
        } elseif (eregi('iPhone', $Agent)) {
            $os = '苹果';
        } else if (eregi('sun', $Agent) && eregi('os', $Agent)) {
            $os = 'SunOS';
        } elseif (eregi('ibm', $Agent) && eregi('os', $Agent)) {
            $os = 'IBM OS/2';
        } elseif (eregi('Mac', $Agent) && eregi('PC', $Agent)) {
            $os = 'Macintosh';
        } elseif (eregi('PowerPC', $Agent)) {
            $os = 'PowerPC';
        } elseif (eregi('AIX', $Agent)) {
            $os = 'AIX';
        } elseif (eregi('HPUX', $Agent)) {
            $os = 'HPUX';
        } elseif (eregi('NetBSD', $Agent)) {
            $os = 'NetBSD';
        } elseif (eregi('BSD', $Agent)) {
            $os = 'BSD';
        } elseif (ereg('OSF1', $Agent)) {
            $os = 'OSF1';
        } elseif (ereg('IRIX', $Agent)) {
            $os = 'IRIX';
        } elseif (eregi('FreeBSD', $Agent)) {
            $os = 'FreeBSD';
        } elseif ($os == '') {
            $os = 'Unknown';
        }
        return $os;
    }


    /**
     * 环信授权注册
     * @param  string  $username
     * @param  string  $password
     * @return bool
     */
    protected function easemobRegister($username,$password){
        $url = $this->getMainUrl().'/users';
        $data= array('username'=>$username,'password'=>$password);
        $data= json_encode($data);
        $header = array($this->getEasemobToken());

        $re_data = $this->getDataCurl($url,$data,$header);
        if($re_data['error']){
            $this->error = $re_data['error_description'];
            return false;
        }
        return true;
    }

    /**
     * 修改环信上用户昵称
     * @param  string  $username
     * @param  string  $nickname
     * @return bool
     */
    protected function easemobSaveNickname($username,$nickname){
        $url = $this->getMainUrl().'/users/'.$username;
        $data= array('nickname'=>$nickname);
        $data= json_encode($data);
        $header = array($this->getEasemobToken());

        $re_data = $this->getDataCurl($url,$data,$header);
        if($re_data['error']){
            $this->error = $re_data['error_description'];
            return false;
        }
        return true;
    }

    /**
     * 获取环信主干URL
     */
    protected function getMainUrl(){
        $appkey = C('EASEMOB.APPKEY');
        $appkey_arr = explode('#', $appkey);
        $org_name = $appkey_arr[0];
        $app_name = $appkey_arr[1];
        $url  = 'https://a1.easemob.com/'.$org_name.'/'.$app_name;
        return $url;
    }

    /**
     * 获取环信token
     */
    protected function getEasemobToken(){
        $url = $this->getMainUrl().'/token';
        $data = array('grant_type'=>'client_credentials','client_id'=>C('EASEMOB.CLIENT_ID'),'client_secret'=>C('EASEMOB.CLIENT_SECRET'));
        $data = json_encode($data);

        $re_data = $this->getDataCurl($url,$data,$header=array());

        return "Authorization:Bearer ".$re_data['access_token'];
    }

    /**
     * CURL
     * @param  string   $url
     * @param  array    $post_data
     * @param  array    $header
     * @param  string   $type
     * @return array
     */
    public function getDataCurl($url,$body,$header,$type="POST"){
        //1.创建一个curl资源
        $ch = curl_init();
        //2.设置URL和相应的选项
        curl_setopt($ch,CURLOPT_URL,$url);//设置url
        //1)设置请求头
        //array_push($header, 'Accept:application/json');
        //array_push($header,'Content-Type:application/json');
        //array_push($header, 'http:multipart/form-data');
        //设置为false,只会获得响应的正文(true的话会连响应头一并获取到)
        curl_setopt($ch,CURLOPT_HEADER,0);
        curl_setopt ( $ch, CURLOPT_TIMEOUT,5); // 设置超时限制防止死循环
        //设置发起连接前的等待时间，如果设置为0，则无限等待。
        curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,5);
        //将curl_exec()获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //2)设备请求体
        if (count($body)>0) {
            //$b=json_encode($body,true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);//全部数据使用HTTP协议中的"POST"操作来发送。
        }
        //设置请求头
        if(count($header)>0){
            curl_setopt($ch,CURLOPT_HTTPHEADER,$header);
        }
        //上传文件相关设置
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);// 对认证证书来源的检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);// 从证书中检查SSL加密算
        
        //3)设置提交方式
        switch($type){
            case "GET":
                curl_setopt($ch,CURLOPT_HTTPGET,true);
                break;
            case "POST":
                curl_setopt($ch,CURLOPT_POST,true);
                break;
            case "PUT"://使用一个自定义的请求信息来代替"GET"或"HEAD"作为HTTP请求。这对于执行"DELETE" 或者其他更隐蔽的HTT
                curl_setopt($ch,CURLOPT_CUSTOMREQUEST,"PUT");
                break;
            case "DELETE":
                curl_setopt($ch,CURLOPT_CUSTOMREQUEST,"DELETE");
                break;
        }
        
        //4)在HTTP请求中包含一个"User-Agent: "头的字符串。-----必设
        curl_setopt($ch, CURLOPT_USERAGENT, 'SSTS Browser/1.0');
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.0; Trident/4.0)' ); // 模拟用户使用的浏览器

        //3.抓取URL并把它传递给浏览器
        $res = curl_exec($ch);
    
        $result = json_decode($res,true);
        //4.关闭curl资源，并且释放系统资源
        curl_close($ch);
        if(empty($result))
            return $res;
        else
            return $result;
    }

}