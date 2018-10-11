<?php
/**
 * Author: ' Silent
 * Time: 2017/9/4 16:16
 */

namespace Pc\Model;


use Think\Model;

class AnnexModel extends BaseModel
{
    // 定义表名
    const TABLENAME = 'attachment';

    /**
     * 记录图片上传
     * @param $data
     */
    public static function addAnnex($data, $imagewidth, $imageheight, $isThumb)
    {
        $ImgData = array();
        $ImgData['url'] = sprintf('/Public/Uploads%s%s', $data['savepath'], $data['savename']);
        $ImgData['imagewidth'] = $imagewidth;
        $ImgData['imageheight'] = $imageheight;
        $ImgData['imagetype'] = $data['ext'];
        $ImgData['filesize'] = $data['size'];
        $ImgData['mimetype'] = $data['type'];
        $ImgData['createtime'] = time();
        $ImgData['uploadtime'] = time();
        $ImgData['sha1'] = $data['sha1'];
        $ImgData['is_thumb'] = $isThumb ? 1 : 0;
        return M(self::TABLENAME)->add($ImgData);
    }

    /**
     * 查看图片之前是否被上传
     * @param $sha1
     */
    public static function getImgSha1($sha1)
    {
        return M(self::TABLENAME)->where(array('sha1' => $sha1))->getField('url');
    }
}