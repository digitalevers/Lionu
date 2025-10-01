<?php
namespace App\Libraries\MarketingApiBaidu\SEM\AdvManage;

class AdvGroup{
    
    /**
     * 对应百度投放后台的  "单元" 模块
     * @param unknown $userName     帐户登录名
     * @param unknown $accessToken  授权token
     * @param int     $idType       查询层级 3-计划ID  5-单元ID
     * @param array   $ids          授权token
     * 
     * @return mixed
     */
    public function getAllAdvGroups($userName, $accessToken, $idType, $ids){
        $api = 'https://api.baidu.com/json/sms/service/AdgroupService/getAdgroup';
        $bodyArr = [
            "header"=>["accessToken"=>$accessToken,"userName"=>$userName],
            "body"=>[
                "adgroupFields"=>[
                    "adgroupId", "campaignId", "adgroupName", "maxPrice", "negativeWords", "exactNegativeWords", "status",
                    "adType", "createTime"
                ],
                "idType"=>$idType,
                'ids'=>$ids
            ]
        ];
        $body = json_encode($bodyArr);
        $header = ['Content-Type: application/json;charset=utf-8'];
        $res = requestPost($api, $body, $header);
        $resArr = json_decode($res, true);
        return $resArr;
    }
    
    
    /**
     * Baidu
     * 修改广告组
     * @param unknown $key
     * @param unknown $accessToken
     * @param array $campaignIDs
     * @return mixed
     */
    public function updateAdvGroup($userName, $accessToken, $body = []){
        $api = 'https://api.baidu.com/json/sms/service/AdgroupService/updateAdgroup';
        $dataArr = [
            "header"=>["accessToken"=>$accessToken,"userName"=>$userName],
            "body"=>$body
        ];
        $payload = json_encode($dataArr);
        $header = ['Content-Type: application/json;charset=utf-8'];
        $res = requestPost($api, $payload, $header);
        $resArr = json_decode($res, true);
        return $resArr;
    }
    
}