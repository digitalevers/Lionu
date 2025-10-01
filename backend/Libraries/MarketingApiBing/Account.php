<?php
namespace App\Libraries\MarketingApiBing;

defined('FCPATH') OR exit('No direct script access allowed');

/**
 * Microsoft Bing Marketing api封装调用库
 * 
 * bing获取数据的接口数据格式与其他渠道有很大不同 
 * 
 * bing的基础信息和余额要从两个不同的接口获取
 * 每日预算也不属于帐户层面      而是归属于广告计划层面
 * @author Administrator
 */
class Account {
    
    public function getInfo($devToken, $authToken, $aid){
        //查询主体和帐户昵称
        $accountApi = 'https://clientcenter.api.bingads.microsoft.com/CustomerManagement/v13/Account/Query';
        $bodyArr = [
            "AccountId"=>$aid
        ];
        $body = json_encode($bodyArr);
        $header = ['Content-Type: application/json;charset=utf-8', 'AuthenticationToken:'.$authToken, 'DeveloperToken:'.$devToken];
        $res = requestPost($accountApi, $body, $header);
        $account = json_decode($res,true);
        
        //查询余额
        $balanceApi = 'https://clientcenter.api.bingads.microsoft.com/CustomerBilling/v13/InsertionOrders/Search';
        $bodyArr = [
            "Predicates"=> [
                [
                    "Field"=> "AccountId",
                    "Operator"=> "Equals",
                    "Value"=> "{$aid}"
                ]
            ],
            "PageInfo"=> [
                "Index"=> 0,
                "Size"=> 1
            ]
        ];
        $body = json_encode($bodyArr);
        $header = ['Content-Type: application/json;charset=utf-8', 'AuthenticationToken:'.$authToken, 'DeveloperToken:'.$devToken];
        $res = requestPost($balanceApi, $body, $header);
        $balance = json_decode($res,true);
        return ['account'=>$account, 'balance'=>$balance];
    }
    
}