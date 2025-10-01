<?php
namespace App\Libraries\MarketingApi360\SEM\AdvReport;

defined('FCPATH') OR exit('No direct script access allowed');
/**
 * 360 Marketing api封装调用库
 * 推广组数据报告
 * @author Administrator
 *
 */
class Creative {
    
    /**
     * 实时报告
     */
    public function reportToday($key, $accessToken, $type = 'all', $page = 1){
        $api = 'https://api.e.360.cn/dianjing/report/creativeNow';
        $body = 'level=account&type='.$type.'&page='.$page;
        $header = ['Content-Type: application/x-www-form-urlencoded;charset=utf-8','apiKey:'.$key,'accessToken:'.$accessToken];
        $res = requestPost($api, $body, $header);
        $resArr = json_decode($res, true);
        return $resArr;
    }
    
    /**
     * 结算报告
     */
    public function reportHistory($key, $accessToken, $startDate = '', $endDate = '', $type = 'all', $page = 1){
        $api = 'https://api.e.360.cn/dianjing/report/creative';
        empty($startDate) && ($startDate = date('Y-m-d',time() - 24 * 3600 * 90));
        empty($endDate) && ($endDate = date('Y-m-d',time() - 24 * 3600));
        $body = 'level=account&startDate='.$startDate.'&endDate='.$endDate.'&type='.$type.'&page='.$page;
        $header = ['Content-Type: application/x-www-form-urlencoded;charset=utf-8','apiKey:'.$key,'accessToken:'.$accessToken];
        $res = requestPost($api, $body, $header);
        $resArr = json_decode($res, true);
        return $resArr;
    }
    
}