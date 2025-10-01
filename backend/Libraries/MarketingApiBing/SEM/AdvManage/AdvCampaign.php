<?php
namespace App\Libraries\MarketingApiBing\SEM\AdvManage;

class AdvCampaign{
    
    /**
     * 获取Bing广告计划基础信息
     * @param unknown $devToken
     * @param unknown $authToken
     * @param unknown $aid      Bing帐户id
     * @return mixed
     */
    public function getAllCampaigns($devToken, $authToken, $aid){
        $campaignApi = 'https://campaign.api.bingads.microsoft.com/CampaignManagement/v13/Campaigns/QueryByAccountId';
        $bodyArr = [
            "AccountId"=>$aid,
            "CampaignType"=>"Audience"
        ];
        $body = json_encode($bodyArr);
        $header = ['Content-Type: application/json;charset=utf-8', 'AuthenticationToken:'.$authToken, 'DeveloperToken:'.$devToken, 'CustomerAccountId:'. $aid];
        $res = requestPost($campaignApi, $body, $header);
        $campaigns = json_decode($res,true);
        return $campaigns;
    }
    
    /**
     * 获取Bing广告计划设置
     * @param unknown $devToken
     * @param unknown $authToken
     * @param unknown $aid
     */
    public function getCampaignSettings($devToken, $authToken, $aid, $media_campaignid){
        $campaignApi = 'https://campaign.api.bingads.microsoft.com/CampaignManagement/v13/CampaignCriterions/QueryByIds';
        $bodyArr = [
            "CampaignId"=>$media_campaignid,
            "CriterionType"=>"Device"
        ];
        $body = json_encode($bodyArr);
        $header = ['Content-Type: application/json;charset=utf-8', 'AuthenticationToken:'.$authToken, 'DeveloperToken:'.$devToken, 'CustomerAccountId:'. $aid];
        $res = requestPost($campaignApi, $body, $header);
        $result = json_decode($res,true);
        return $result;
    }
    
    /**
     * 更新Bing广告计划
     * @param unknown $devToken
     * @param unknown $authToken
     * @param unknown $aid      Bing帐户id
     * @return mixed
     */
    public function updateCampaigns($devToken, $authToken, $aid, $updateData){
        $campaignApi = 'https://campaign.api.bingads.microsoft.com/CampaignManagement/v13/Campaigns';
        $bodyArr = [
            "AccountId"=>$aid,
            "Campaigns"=>[
                $updateData
            ]
        ];
        $body = json_encode($bodyArr);
        $header = ['Content-Type: application/json;charset=utf-8', 'AuthenticationToken:'.$authToken, 'DeveloperToken:'.$devToken, 'CustomerAccountId:'. $aid];
        $res = requestPost($campaignApi, $body, $header, 'PUT');
        $campaigns = json_decode($res,true);
        return $campaigns;
    }
    
}
