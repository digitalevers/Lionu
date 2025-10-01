<?php
namespace App\Libraries\MarketingApi360\SEM\AdvManage;

class AdvCampaign{
    
    /**
     * 360
     * 获取某个帐号下所有的推广计划 IDs
     * @param array $params
     */
    public function getAllCampaigns($key, $accessToken){
        //请求某个帐号下的所有推广计划id
        $api = 'https://api.e.360.cn/dianjing/campaign/getCampaignIdList';
        $body = '';         //以url参数拼接的方式传递
        $header = ['Content-Type: application/x-www-form-urlencoded;charset=utf-8','apiKey:'.$key,'accessToken:'.$accessToken];
        $res = requestPost($api, $body, $header);
        $resArr = json_decode($res, true);
        return $resArr;
    }
    
    /**
     * 360
     * 根据推广计划ID批量获取推广计划详情
     */
    public function getCampaignDetail($key, $accessToken, $campaignIDs = []){
        $api = 'https://api.e.360.cn/dianjing/campaign/getInfoByIdList';
        $body = 'idList='.json_encode($campaignIDs);         //以url参数拼接的方式传递
        $header = ['Content-Type: application/x-www-form-urlencoded;charset=utf-8','apiKey:'.$key,'accessToken:'.$accessToken];
        $res = requestPost($api, $body, $header);
        $resArr = json_decode($res, true);
        return $resArr;
    }
    
    /**
     * 360
     * 修改广告计划
     * @param unknown $key
     * @param unknown $accessToken
     * @param array   $body
     * @return mixed
     */
    public function updateCampaign($key, $accessToken, $body = []){
        $api = 'https://api.e.360.cn/dianjing/campaign/update';
        $body = http_build_query($body);
        $header = ['Content-Type: application/x-www-form-urlencoded;charset=utf-8','apiKey:'.$key,'accessToken:'.$accessToken];
        $res = requestPost($api, $body, $header);
        $resArr = json_decode($res, true);
        return $resArr;
    }
}