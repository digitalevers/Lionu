<?php
namespace App\Libraries\MarketingApiBing\SEM\AdvReport;

defined('FCPATH') OR exit('No direct script access allowed');

/**
 * Bing Marketing api封装调用库
 * @author Administrator
 *
 * 广告活动 报表数据
 */
class Campaign {
    
    //报表需要返回的数据列
    private $columns = [
        "AllConversions","Impressions","Clicks","AccountId","CampaignId","Spend","TimePeriod","DeviceType"
    ];
    
    public function report($devToken, $authToken, $aid, $startDate = '', $endDate = ''){
        $reportRequestId = $this->_getReportId($devToken, $authToken, $aid, $startDate, $endDate);
        sleep(1);   //暂停 1秒等待CSV报表文件生成
        $url = $this->_getReportCsvUrl($devToken, $authToken, $reportRequestId);
        if(!empty($url)){
            $reportData = $this->_parseCsv($url);
            return $reportData;
        } else {
            return [];
        }
    }
    
    //1.获取报表id
    private function _getReportId($devToken, $authToken, $aid, $startDate, $endDate){
        $startDate = array_map('intval', explode('-', $startDate));
        $endDate = array_map('intval', explode('-', $endDate));
        $api = 'https://reporting.api.bingads.microsoft.com/Reporting/v13/GenerateReport/Submit';
        $bodyArr = [
            "ReportRequest" => [
                "Type" =>"CampaignPerformanceReportRequest",
                "Aggregation" =>"Daily",
                "Columns" =>$this->columns,
                "Scope" => [
                    "AccountIds" =>[
                        "{$aid}"
                    ]
                ],
                "Time" => [
                    "CustomDateRangeStart" => [
                        "Day" =>$startDate[2],
                        "Month" =>$startDate[1],
                        "Year" =>$startDate[0]
                    ],
                    "CustomDateRangeEnd" => [
                        "Day" =>$endDate[2],
                        "Month" =>$endDate[1],
                        "Year" =>$endDate[0]
                    ],
                ]
            ]
        ];
        $body = json_encode($bodyArr);
        $header = ['Content-Type: application/json;charset=utf-8', 'AuthenticationToken:'.$authToken, 'DeveloperToken:'.$devToken];
        $res = requestPost($api, $body, $header);
        $reportRes = json_decode($res,true);
        $reportRequestId = $reportRes['ReportRequestId'];
        
        return $reportRequestId;
    }
    
    //2.根据报表id获取报表url
    private function _getReportCsvUrl($devToken, $authToken, $reportRequestId){
        $api = 'https://reporting.api.bingads.microsoft.com/Reporting/v13/GenerateReport/Poll';
        $bodyArr = ["ReportRequestId"=> "{$reportRequestId}"];
        $body = json_encode($bodyArr);
        $header = ['Content-Type: application/json;charset=utf-8', 'AuthenticationToken:'.$authToken, 'DeveloperToken:'.$devToken];
        $res = requestPost($api, $body, $header);
        $reportUrlRes = json_decode($res,true);
        $reportUrl = $reportUrlRes['ReportRequestStatus']['ReportDownloadUrl'];
        return $reportUrl;
    }
    
    //3.下载url的csv文件并解析出广告报表数据
    function _parseCsv($url) {
        // 下载 ZIP 文件到内存
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        $zipData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (curl_error($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \Exception('下载错误: ' . $error);
        }
        curl_close($ch);
        if ($httpCode !== 200) {
            throw new \Exception('下载失败，HTTP状态码: ' . $httpCode);
        }
        
        // 创建临时 ZIP 文件
        $tempZipFile = tempnam(sys_get_temp_dir(), 'zip_') . '.zip';
        file_put_contents($tempZipFile, $zipData);
        try {
            // 使用 ZipArchive 解压
            $zip = new \ZipArchive();
            if ($zip->open($tempZipFile) === TRUE) {
                $csvContent = '';
                // 查找并读取 CSV 文件
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $filename = $zip->getNameIndex($i);
                    if (pathinfo($filename, PATHINFO_EXTENSION) === 'csv') {
                        $csvContent = $zip->getFromIndex($i);
                        break;
                    }
                }
                $zip->close();
                if (empty($csvContent)) {
                    throw new \Exception('ZIP 文件中未找到 CSV 文件');
                } 
                // 解析 CSV 内容
                $rows = [];
                $_lines = explode("\n", $csvContent);
                //去掉数组前十项和最后一项
                $lines = array_values(array_slice($_lines, 11, -3)); 
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (!empty($line)) {
                        $row = str_getcsv($line);
                        $_row = [];
                        foreach ($row as $index=>$v){
                            $_row[$this->columns[$index]] = $v;
                        }
                        $rows[] = $_row;
                    }
                }
                return $rows;
            } else {
                throw new \Exception('无法打开 ZIP 文件');
            }
        } finally {
            // 清理临时文件
            if (file_exists($tempZipFile)) {
                unlink($tempZipFile);
            }
        }
    }
    
}