<?php
/**
 * UploadsController.class.php
 * @Author chendg
 * @Date 2015-02-14 15:27:30
 * 图片上传
 */

namespace Pc\Controller;
use Think\Controller;

class UploadsController extends Controller {
	/*上传文件*/
	/*public function upload(){
		import("ORG.Net.UploadFile");
		$upload = new UploadFile(); // 实例化上传类
		$dir = C("__UPLOADURL__").'/'.$dirname;
		//$this->mkdirs($dir);
		$upload->maxSize  = 3145728 ;// 设置附件上传大小
		$upload->allowExts  = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
		$upload->savePath  = $dir;// 设置附件上传目录
		$info = $upload->upload();
		if(!$info) {// 上传错误提示错误信息
			echo $upload->getErrorMsg();
		}else{// 上传成功 获取上传文件信息
			$result_url = $upload->getUploadFileInfo();
			$fn=$_GET['CKEditorFuncNum'];
			$str = '<script type="text/javascript">window.parent.CKEDITOR.tools.callFunction('.$fn.', \''.$result_url.'\');</script>';
			exit($str);
		}
	}*/

	public function upload(){
		$upload = new \Think\Upload();              // 实例化上传类
        $name = time().rand();	                    //设置上传图片的规则
        $date = date('Ymd',time());			//当前日期
        $dir = './Uploads/'.$date."/";
        $url = '/Uploads/'.$date."/";
        $this->mkdirs($dir);

        $upload->maxSize = 3145728 ;                                // 设置附件上传大小
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg', 'swf');  // 设置附件上传类型
        $upload->rootPath = $dir;                                   // 设置上传根目录
        $upload->saveName = $name;
        $upload->savePath = '';                                     // 设置附件上传目录
        $upload->autoSub = false;                                   // 设置附件上传目录

        $info = $upload->uploadOne($_FILES['upload']);
        if(!$info) {                        // 上传错误提示错误信息
            //$this->error( $upload->getError() );
            echo $upload->getError();
        }else{                               // 上传成功 获取上传文件信息
            $result_url = $url.$info['savepath'].$info['savename'];
            $fn=$_GET['CKEditorFuncNum'];
			$str = '<script type="text/javascript">window.parent.CKEDITOR.tools.callFunction('.$fn.', \''.$result_url.'\');</script>';
			exit($str);
        }
	}

	/*创建多级目录*/
    public function mkdirs($dir, $mode=0777){
        return is_dir($dir) || ( $this->mkdirs(dirname($dir)) && mkdir($dir,$mode) );
    }
}