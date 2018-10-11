<?php
/**
 * Author: ' Silent
 * Time: 2017/9/4 15:58
 */

namespace Pc\Controller;

use Pc\Model\AnnexModel;
use Think\Image;
use Think\Upload;
use Think\Controller;

class UploadController extends Controller
{

    /**
     * 上传图片
     * @param string $path
     * @param bool $isThumb
     * @param int $thumbWidth
     * @param int $thumbHeigth
     */
    public function uploadImg($path = '')
    {
        $info = array('code' => 1, 'message' => '');
        $upload = new Upload();
        $upload->rootPath = $this->getRootPath();
        $upload->maxSize = 2 * 1024 * 1024 + 1; // 设置附件上传大小<=2M
        $upload->exts = array('xlsx','csv','xls'); // 设置附件上传类型
        $upload->savePath = $path . '/'; // 设置附件上传目录    // 上传文件
        $upload->saveName = array('uniqid', ''); // 设置附件上传目录    // 上传文件
        if (!file_exists($upload->rootPath)) {
            mkdir($upload->rootPath, 0755, true);
        }
        $result = $upload->upload();
        if (!$result) {
            $info['error'] = 1;
            $info['message'] = $upload->getError();
        } else {
            $imageThumb = new Image();
            foreach ($result as $k => $v) {
                $imageUrl = sprintf('%s%s%s', $this->getRootPath(), $v['savepath'], $v['savename']); //生成完整路径
                $url = sprintf('/Uploads/%s%s', $v['savepath'], $v['savename']);
                $info['message'][] = $url;
            }
            $info['error'] = 0;
        }
        $this->ajaxReturn($info);
    }

    /**
     * 获取网站根目录
     * @return string
     */
    public function getRootPath()
    {
        return dirname($_SERVER['SCRIPT_FILENAME']) . '/Uploads/';
    }
}