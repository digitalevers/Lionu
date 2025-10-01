<?php
namespace App\Libraries\MarketingApiBing\SEM\AdvManage;

class AdvGroup{
    
    /**
     * 获取 Bing 广告组信息
     * $campaignId 不支持数组 所以想获取某个帐户下的所有广告组信息 需要循环遍历所有的campaignId并请求该接口
     * @param unknown $devToken             
     * @param unknown $authToken
     * @param unknown $aid         Bing帐户id
     * @param unknown $campaignId  Bing广告活动id
     * @return mixed                
     */
    public function getAllAdvGroups($devToken, $authToken, $aid, $campaignId){
        $campaignApi = 'https://campaign.api.bingads.microsoft.com/CampaignManagement/v13/AdGroups/QueryByCampaignId';
        $bodyArr = [
            "CampaignId"=>$campaignId,
        ];
        $body = json_encode($bodyArr);
        $header = ['Content-Type: application/json;charset=utf-8', 'AuthenticationToken:'.$authToken, 'DeveloperToken:'.$devToken, 'CustomerAccountId:'. $aid];
        $res = requestPost($campaignApi, $body, $header);
        $campaigns = json_decode($res,true);
        return $campaigns;
    }
    
    /**
     * 更新 Bing广告组信息 - 更新需要使用PUT请求
     * $campaignId 不支持数组 所以想获取某个帐户下的所有广告组信息 需要循环遍历所有的campaignId并请求该接口
     * @param unknown $devToken
     * @param unknown $authToken
     * @param unknown $aid         Bing帐户id
     * @param unknown $campaignId  Bing广告活动id
     * @return mixed
     */
    public function updateAdvGroup($devToken, $authToken, $aid, $body){
        $updateAdvGroupApi = 'https://campaign.api.bingads.microsoft.com/CampaignManagement/v13/AdGroups';
        //dump($body);
        $body = json_encode($body);
        $header = ['Content-Type: application/json;charset=utf-8', 'AuthenticationToken:'.$authToken, 'DeveloperToken:'.$devToken, 'CustomerAccountId:'. $aid];
        //dump($header);
        //dump($bodyArr);
        $res = requestPost($updateAdvGroupApi, $body, $header, 'PUT');
        $resArr = json_decode($res,true);
        return $resArr;
    }
}