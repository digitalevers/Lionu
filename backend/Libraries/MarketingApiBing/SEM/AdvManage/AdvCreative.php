<?php
namespace App\Libraries\MarketingApiBing\SEM\AdvManage;

class AdvCreative {
    
    /**
     * 获取Bing广告信息
     * AdGroupId 不支持数组 所以想获取某个帐户下的所有广告信息 需要循环遍历所有的 AdvGroupId 并请求该接口
     * @param unknown $devToken             
     * @param unknown $authToken
     * @param unknown $media_accountid   Bing帐户id
     * @param unknown $media_advgroupid  Bing广告活动id
     * @return mixed                
     */
    public function getAllCreatives($devToken, $authToken, $media_accountid, $media_advgroupid){
        $campaignApi = 'https://campaign.api.bingads.microsoft.com/CampaignManagement/v13/Ads/QueryByAdGroupId';
        $bodyArr = [
            "AdGroupId"=>$media_advgroupid,
            "AdTypes"=>["ResponsiveAd"]
        ];
        $body = json_encode($bodyArr);
        $header = ['Content-Type: application/json;charset=utf-8', 'AuthenticationToken:'.$authToken, 'DeveloperToken:'.$devToken, 'CustomerAccountId:'. $media_accountid];

        $res = requestPost($campaignApi, $body, $header);
        $campaigns = json_decode($res,true);
        return $campaigns;
    }
    
    /**
     * 更新Bing广告信息
     * @param unknown $devToken
     * @param unknown $authToken
     * @param unknown $media_accountid   Bing帐户id
     * @param unknown $media_advgroupid  Bing广告活动id
     * @return mixed
     */
    public function updateCreatives($devToken, $authToken, $media_accountid, $advGroupId, $updateData){
        $campaignApi = 'https://campaign.api.bingads.microsoft.com/CampaignManagement/v13/Ads';
        $bodyArr = [
            "AdGroupId"=>$advGroupId,
            "Ads"=>[
                $updateData
            ]
        ];
        $body = json_encode($bodyArr);
        $header = ['Content-Type: application/json;charset=utf-8', 'AuthenticationToken:'.$authToken, 'DeveloperToken:'.$devToken, 'CustomerAccountId:'. $media_accountid];
        $res = requestPost($campaignApi, $body, $header, 'PUT');
        $campaigns = json_decode($res,true);
        return $campaigns;
    }
    
}