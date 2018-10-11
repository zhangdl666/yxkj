<?php

namespace Pc\Server;

/**
 * Description of PhpExcel
 * phpExcel操作类
 * @author company
 */
class PhpExcel {

    protected $PHPExcel;
    protected $PHPReader;
    protected $PHPExcelObj;

    public function __construct($exts = '') {
        Vendor("PHPexcel.PHPExcel");
        //如果excel文件后缀名为.xls，导入这个类
        if ($exts == 'xlsx') {
            Vendor("PHPexcel.PHPExcel.Reader.Excel2007");
        } else if($exts == 'csv'){
            Vendor("PHPexcel.PHPExcel.Reader.CSV");
        }else{
            Vendor("PHPexcel.PHPExcel.Reader.Excel5");
        }
        //如果excel文件后缀名为.xlsx，导入这个类
        if (!$this->PHPExcel) {
            $this->PHPExcel = new \PHPExcel();
        }
        if (!$this->PHPReader) {
            if ($exts == 'xlsx') {
                $this->PHPReader = new \PHPExcel_Reader_Excel2007();
            }else if($exts == 'csv'){
                $this->PHPReader = new \PHPExcel_Reader_CSV();
            } else {
                $this->PHPReader = new \PHPExcel_Reader_Excel5();
            }
        }
    }

    public function getPhpExcel() {
        return $this->PHPExcel;
    }

    public function getPhpExcelWriter5() {
        return new \PHPExcel_Writer_Excel5($this->PHPExcel);
    }

    /**
     * 获取文件对象
     * @param type $filename 文件的绝对路径
     * @return type
     */
    public function getExcelObj($filename) {
        if (!file_exists($filename)) {
            E('读取的文件不存在');
        }
        $this->PHPExcelObj = $this->PHPReader->load($filename);
        return $this->PHPExcelObj;
    }

    /**
     * 读取第一张表数据
     * @param array $keys 自定义键值
     * @return type
     */
    public function readExcel($keys = array()) {
        if (!$this->PHPExcelObj) {
            E('文件对象不存在');
        }
        //获取表中的第一个工作表，如果要获取第二个，把0改为1，依次类推
        $currentSheet = $this->PHPExcelObj->getSheet(0);
        //获取总列数
        $allColumn = $currentSheet->getHighestColumn();
        //获取总行数
        $allRow = $currentSheet->getHighestRow();
        //循环获取表中的数据，$currentRow表示当前行，从哪行开始读取数据，索引值从0开始
        $arr = array();
        for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
            //从哪列开始，A表示第一列
            for ($currentColumn = 'A'; $currentColumn <= $allColumn; $currentColumn++) {
                //数据坐标
                $address = $currentColumn . $currentRow;
                //读取到的数据，保存到数组$arr中
                if ($keys[$currentColumn]) {
                    $arr[$currentRow][$keys[$currentColumn]] = $currentSheet->getCell($address)->getValue();
                } else {
                    $arr[$currentRow][] = $currentSheet->getCell($address)->getValue();
                }
            }
        }
        return $arr;
    }

    /**
     * excel导出
     * @param type $lists 数据源
     * @param type $tableTitle 表头（第一行）
     * @param type $title 标题
     * @param type $admin 操作人
     */
    public function excelDaochu($lists = array(), $tableTitle = array(), $title = "答疑导出", $admin = "admin") {
        /** Mod By W 2017/1/18 -== 最后一列的标记 ==- * */
        $last = chr(65 + count($tableTitle) - 1);
        $PHPExcel = $this->PHPExcel; //这里要注意‘\’ 要有这个。因为版本是3.1.2了。
        $objWriter = new \PHPExcel_Writer_Excel5($PHPExcel); //设置保存版本格式
        $PHPExcel->getProperties()->setCreator("Neo")
                ->setLastModifiedBy("Neo")
                ->setTitle("优才网校")
                ->setSubject("答疑列表")
                ->setDescription("")
                ->setKeywords("答疑列表")
                ->setCategory("");
        $PHPExcel->setActiveSheetIndex(0);
        $PHPExcel->getActiveSheet()->setTitle("答疑列表");
        //填入表头主标题
        $PHPExcel->getActiveSheet()->setCellValue('A1', '答疑列表');
        //填入表头副标题
        $PHPExcel->getActiveSheet()->setCellValue('A2', '操作者：' . $admin . ' 导出日期：' . date('Y-m-d', time()));
        //合并表头单元格
        $PHPExcel->getActiveSheet()->mergeCells('A1:' . $last . '1');
        $PHPExcel->getActiveSheet()->mergeCells('A2:' . $last . '2');

        //设置表头行高
        $PHPExcel->getActiveSheet()->getRowDimension(1)->setRowHeight(40);
        $PHPExcel->getActiveSheet()->getRowDimension(2)->setRowHeight(20);
        $PHPExcel->getActiveSheet()->getRowDimension(3)->setRowHeight(30);

        //设置表头字体
        $PHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setName('黑体');
        $PHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setSize(20);
        $PHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);
        $PHPExcel->getActiveSheet()->getStyle('A2')->getFont()->setName('宋体');
        $PHPExcel->getActiveSheet()->getStyle('A2')->getFont()->setSize(14);
        $PHPExcel->getActiveSheet()->getStyle('A3:' . $last . '3')->getFont()->setBold(true);

        //设置单元格边框
        $styleArray = array(
            'borders' => array(
                'allborders' => array(
                    'style' => \PHPExcel_Style_Border::BORDER_THIN, //细边框
                ),
            ),
        );
        $hang = 4;
        foreach ($tableTitle as $k => $v) {
            $PHPExcel->getActiveSheet()->getColumnDimension(chr(65 + $k) . "")->setWidth(20); //表格宽度
            $PHPExcel->getActiveSheet()->setCellValue(chr(65 + $k) . "3", $v); //表格表头
        }

        foreach ($lists as $list) {
            foreach ($list as $key => $val) {
                $PHPExcel->getActiveSheet()->setCellValue('' . chr(65 + $key) . ($hang), $val); //填充类容
            }
            $hang += 1;
        }
        $hang -= 1;



        //设置单元格边框
        $PHPExcel->getActiveSheet()->getStyle('A1:' . $last . '' . $hang)->applyFromArray($styleArray);
        //设置自动换行
        $PHPExcel->getActiveSheet()->getStyle('A4:' . $last . '' . $hang)->getAlignment()->setWrapText(true);
        //设置字体大小
        $PHPExcel->getActiveSheet()->getStyle('A4:' . $last . '' . $hang)->getFont()->setSize(12);
        //垂直居中
        $PHPExcel->getActiveSheet()->getStyle('A1:' . $last . '' . $hang)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        //水平居中
        $PHPExcel->getActiveSheet()->getStyle('A1:' . $last . '' . $hang)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        //接下来当然是下载这个表格了，在浏览器输出就好了
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header('Content-Disposition:attachment;filename=' . $title . '.xls');
        header("Content-Transfer-Encoding:binary");
//        $objWriter->save($title . '.xls');
        $objWriter->save('php://output');
    }

}
