<?php
namespace App\Libraries\MarketingApiBaidu\SEM\AdvManage;

class AdvCampaign{
    
    /**
     * Baidu
     * 对应百度投放后台的  "方案" 模块
     * @param unknown $userName     帐户登录名
     * @param unknown $accessToken  授权token
     * @return mixed
     */
    public function getAllCampaigns($userName, $accessToken){
        $api = 'https://api.baidu.com/json/sms/service/CampaignService/getCampaign';
        $dataArr = [
            "header"=>["accessToken"=>$accessToken,"userName"=>$userName],
            "body"=>[
                "campaignFields"=>[
                    "campaignId", "campaignName", "budget", "regionTarget", "negativeWords", "exactNegativeWords",
                    "schedule", "status", "equipmentType", "createTime", "campaignBidType", "campaignBid","campaignOcpcBidType","campaignOcpcBid",
                    "paDevice", "os"
                ]    
             ]
        ];
        $payload = json_encode($dataArr);
        $header = ['Content-Type: application/json;charset=utf-8'];
        $res = requestPost($api, $payload, $header);
        $resArr = json_decode($res, true);
        return $resArr;
    }
    
    /**
     * Baidu
     * 修改广告计划
     * @param unknown $key
     * @param unknown $accessToken
     * @param array $campaignIDs
     * @return mixed
     */
    public function updateCampaign($userName, $accessToken, $body = []){
        $api = 'https://api.baidu.com/json/sms/service/CampaignService/updateCampaign';
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
