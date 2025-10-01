<?php
namespace App\Libraries\MarketingApiBaidu\SEM\AdvReport;

defined('FCPATH') OR exit('No direct script access allowed');
/**
 * baidu Marketing api封装调用库
 * 推广组数据报告
 * @author Administrator
 *
 */
class Group {
    
    /**
     * @param unknown $userName     广告用户名 (登录后右上角用户区)
     * @param unknown $accessToken  应用Token (https://dev2.baidu.com/appmanage)
     * @param string $startDate     查找数据起始日期
     * @param string $endDate       查找数据结束日期
     * @param string $type
     * @return mixed
     */
    public function report($userName, $accessToken, $startDate = '', $endDate = ''){
        $api = 'https://api.baidu.com/json/sms/service/OpenApiReportService/getReportData';
        $bodyArr = [
            'header'=>['accessToken'=>$accessToken, 'userName'=>$userName],
            'body'=>[
                'reportType'=>2284618,
                'timeUnit'=>'DAY',
                'startDate'=>$startDate,
                'endDate'=>$endDate,
                'columns'=>["date","userName","campaignNameStatus","campaignStatus","campaignId","adGroupId","adGroupName","impression","click","cost","device","ocpcConversionsDetail12"]
            ]
        ];
        $body = json_encode($bodyArr);
        $header = ['Content-Type: application/json;charset=utf-8'];
        $res = requestPost($api, $body, $header);
        $resArr = json_decode($res, true);
        return $resArr;
    }
    
}