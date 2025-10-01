<?php
namespace App\Libraries\MarketingApi360\SEM\AdvManage;

class AdvCreative{
    
    /**
     *
     * 360
     * 获取某个广告组下所有的创意
     * @param unknown $key          帐户key
     * @param unknown $accessToken  帐户token
     * @param unknown $groupId      广告组ID
     * @return mixed
     */
    public function getAllCreativeIDs($key, $accessToken, $groupId){
        //请求某个帐号下的所有推广计划id
        $api = 'https://api.e.360.cn/dianjing/creative/getIdListByGroupId';
        $body = 'groupId='.$groupId;
        $header = ['Content-Type: application/x-www-form-urlencoded;charset=utf-8','apiKey:'.$key,'accessToken:'.$accessToken];
        $res = requestPost($api, $body, $header);
        $resArr = json_decode($res, true);
        return $resArr;
    }
    
    /**
     * 360
     * 根据创意ID批量获取创意详情
     * @param unknown $key          帐户key
     * @param unknown $accessToken  帐户token
     * @param unknown $creativeIDs   创意ID列表
     * @return mixed
     */
    public function getCreativeDetail($key, $accessToken, $creativeIDs = []){
        $api = 'https://api.e.360.cn/dianjing/creative/getInfoByIdList';
        $body = 'idList='.json_encode($creativeIDs);         //以url参数拼接的方式传递
        $header = ['Content-Type: application/x-www-form-urlencoded;charset=utf-8','apiKey:'.$key,'accessToken:'.$accessToken];
        $res = requestPost($api, $body, $header);
        $resArr = json_decode($res, true);
        return $resArr;
    }
    
    /**
     * 360
     * 修改创意信息
     * @param unknown $key
     * @param unknown $accessToken
     * @param array   $creativeInfos  更新的创意信息列表
     * @return mixed
     */
    public function updateCreative($key, $accessToken, $creativeInfos = []){
        $api = 'https://api.e.360.cn/dianjing/creative/update';
        $body = 'creatives='.json_encode($creativeInfos);         //以url参数拼接的方式传递
        $header = ['Content-Type: application/x-www-form-urlencoded;charset=utf-8','apiKey:'.$key,'accessToken:'.$accessToken];
        $res = requestPost($api, $body, $header);
        $resArr = json_decode($res, true);
        return $resArr;
    }
}