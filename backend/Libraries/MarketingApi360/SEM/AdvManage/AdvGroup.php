<?php
namespace App\Libraries\MarketingApi360\SEM\AdvManage;

class AdvGroup{
    
    /**
     *
     * 360
     * 获取某个推广计划下所有的推广组
     * @param unknown $key          帐户key
     * @param unknown $accessToken  帐户token
     * @param unknown $campaignId   推广计划ID
     * @return mixed
     */
    public function getAllAdvGroups($key, $accessToken, $campaignId){
        //请求某个帐号下的所有推广计划id
        $api = 'https://api.e.360.cn/dianjing/group/getIdListByCampaignId';
        $body = 'campaignId='.$campaignId;       
        $header = ['Content-Type: application/x-www-form-urlencoded;charset=utf-8','apiKey:'.$key,'accessToken:'.$accessToken];
        $res = requestPost($api, $body, $header);
        $resArr = json_decode($res, true);
        return $resArr;
    }
    
    /**
     * 360
     * 根据推广组ID批量获取推广组详情
     * @param unknown $key          帐户key
     * @param unknown $accessToken  帐户token
     * @param unknown $campaignId   推广组ID列表
     * @return mixed
     */
    public function getAdvGroupDetail($key, $accessToken, $advGroupIDs = []){
        $api = 'https://api.e.360.cn/dianjing/group/getInfoByIdList';
        $body = 'idList='.json_encode($advGroupIDs);         //以url参数拼接的方式传递
        $header = ['Content-Type: application/x-www-form-urlencoded;charset=utf-8','apiKey:'.$key,'accessToken:'.$accessToken];
        $res = requestPost($api, $body, $header);
        $resArr = json_decode($res, true);
        return $resArr;
    }
    
    /**
     * 360
     * 修改广告组
     * @param unknown $key          帐户key
     * @param unknown $accessToken  帐户token
     * @param unknown $data         修改广告组所需数据
     * @return mixed
     */
    public function updateAdvGroup($key, $accessToken, $data = []){
        $api = 'https://api.e.360.cn/dianjing/group/update';
        $body = http_build_query($data);
        $header = ['Content-Type: application/x-www-form-urlencoded;charset=utf-8','apiKey:'.$key,'accessToken:'.$accessToken];
        $res = requestPost($api, $body, $header);
        $resArr = json_decode($res, true);
        return $resArr;
    }
}