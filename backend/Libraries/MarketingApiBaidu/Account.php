<?php
namespace App\Libraries\MarketingApiBaidu;

defined('FCPATH') OR exit('No direct script access allowed');
/**
 * 百度 Marketing api封装调用库
 * @author Administrator
 *
 */
class Account {
    
    /**
     * 获取BAIDU广告投放帐号信息
     * @param unknown $userName     广告用户名 (登录后右上角用户区)
     * @param unknown $accessToken  应用Token (https://dev2.baidu.com/appmanage)
     * @return mixed
     */
    public function getInfo($userName, $accessToken){
        $api = 'https://api.baidu.com/json/sms/service/AccountService/getAccountInfo';
        $bodyArr = [
            "header"=>["accessToken"=>$accessToken,"userName"=>$userName],
            "body"=>["accountFields"=>["userId", "balance", "budget", "pcBalance", "budgetType", "cost", "excludeIp", "openDomains", "payment", "regDomain", "regionTarget", "userStat", "cid","liceName"]]
        ];
        $body = json_encode($bodyArr);
        $header = ['Content-Type: application/json;charset=utf-8'];
        $res = requestPost($api, $body, $header);
        $resArr = json_decode($res, true);
        return $resArr;
    }
    
    
    /**
     * 设置推广账号的每日预算
     */
    public function setDayBudget($userName, $accessToken, $budget){
        $api = 'https://api.baidu.com/json/sms/service/AccountService/updateAccountInfo';
        $bodyArr = [
            "header"=>["accessToken"=>$accessToken,"userName"=>$userName],
            "body"=>["accountInfo"=>["budget"=>$budget, "budgetType"=>1]]
        ];
        $body = json_encode($bodyArr);
        $header = ['Content-Type: application/json;charset=utf-8'];
        $res = requestPost($api, $body, $header);
        $resArr = json_decode($res, true);
        return $resArr;
    }
    
    
    
}