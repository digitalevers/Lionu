<?php
namespace App\Libraries\MarketingApiTencent;

defined('FCPATH') OR exit('No direct script access allowed');

/**
 * Tencent搜索  Marketing api封装调用库
 * Tencent搜索 获取数据的接口数据格式与其他渠道有很大不同 
 * 
 * 和Bing一样 Tencent搜索的基础信息和余额也要从两个不同的接口获取
 * 帐户层面有日预算数据    广告计划层面也有日预算数据
 * 详情见文档  https://developers.e.qq.com/docs/api/account/advertiser/advertiser_daily_budget_get
 * 
 * @author Administrator
 */
class Account {
    private $version = 'v1.3';
    
    public function getInfo($accessToken, $mediaAccountId){
        //查询主体和帐户昵称
        $accountApi = 'https://api.e.qq.com/'.$this->version.'/advertiser/get';
        $commonParameters = array (
            'access_token' => $accessToken,
            'timestamp' => time(),
            'nonce' => md5(uniqid('', true))
        );
        $parameters = array (
            'account_id' => $mediaAccountId,
            'filtering' =>[[
                'field' => 'corporation_name',
                'operator' => 'EQUALS',
                'values' =>['腾讯计算机系统有限公司']
            ]],
            'fields' =>['account_id'],
            'pagination_mode' => 'PAGINATION_MODE_NORMAL',
            'page' => 1,
            'page_size' => 10,
        );
        $parameters = array_merge($commonParameters, $parameters);
        foreach ($parameters as $key => $value) {
            if (!is_string($value)) {
                $parameters[$key] = json_encode($value);
            }
        }
        $requestUrl = $accountApi . '?' . http_build_query($parameters);
        $res = requestGet($requestUrl);
        $account = json_decode($res,true);
        
        //查询余额
        /* $balanceApi = 'https://clientcenter.api.bingads.microsoft.com/CustomerBilling/v13/InsertionOrders/Search';
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
        $balance = json_decode($res,true); */
        $balance = 100;
        
        return ['account'=>$account, 'balance'=>$balance];
    }
    
}