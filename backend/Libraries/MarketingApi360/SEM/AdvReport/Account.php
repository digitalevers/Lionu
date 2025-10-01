<?php
namespace App\Libraries\MarketingApi360\SEM\AdvReport;

defined('FCPATH') OR exit('No direct script access allowed');
/**
 * 360 Marketing api封装调用库
 * @author Administrator
 *
 */
class Account {
    
    /**
     * 实时 & 结算报告
     */
    public function report($key, $accessToken, $startDate = '', $endDate = '', $type = ''){
        $api = 'https://api.e.360.cn/dianjing/report/accountDaily';
        empty($startDate) && ($startDate = date('Y-m-d',time()));
        empty($endDate) && ($endDate = date('Y-m-d',time()));
        $body = 'startDate='.$startDate.'&endDate='.$endDate.'&type='.$type;
        $header = ['Content-Type: application/x-www-form-urlencoded;charset=utf-8','apiKey:'.$key,'accessToken:'.$accessToken];
        $res = requestPost($api, $body, $header);
        $resArr = json_decode($res, true);
        return $resArr;
    }
    
}