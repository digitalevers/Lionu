<?php

namespace App\Libraries\MarketingApi360\SEM\AdvOthers;

defined('FCPATH') OR exit('No direct script access allowed');

/**
 * 360 Marketing api封装调用库
 * @author Administrator
 *
 */
class Ocpc {
    
    /**
     * ocpc v1 获取所有ocpc投放包id
     */
    public function ocpcList($key, $accessToken, $body = []){
        $api = 'https://api.e.360.cn/dianjing/ocpc/Getidlist';
        $body = count($body) > 0 ? http_build_query($body) : '';
        $header = ['Content-Type: application/x-www-form-urlencoded;charset=utf-8','apiKey:'.$key,'accessToken:'.$accessToken];
        $res = requestPost($api, $body, $header);
        $resArr = json_decode($res, true);
        return $resArr;
    }
    
    /**
     * 根据ocpc投放包id 获取所有关联的广告计划id
     * ocpc v1 获取的投放包id 列表
     */
    public function ocpcCampaignIds($key, $accessToken, $ids = []){
        $api = 'https://api.e.360.cn/dianjing/ocpc/Getinfobyids';
        $body = 'ids='.json_encode($ids);
        $header = ['Content-Type: application/x-www-form-urlencoded;charset=utf-8','apiKey:'.$key,'accessToken:'.$accessToken];
        $res = requestPost($api, $body, $header);
        $resArr = json_decode($res, true);
        return $resArr;
    }
    
    /**
     * 更新ocpc包信息
     */
    public function ocpcUpdate($key, $accessToken, $ocpcs = []){
        $api = 'https://api.e.360.cn/dianjing/ocpc/batchupdate';
        $body = 'ocpcs='.json_encode($ocpcs);
        $header = ['Content-Type: application/x-www-form-urlencoded;charset=utf-8','apiKey:'.$key,'accessToken:'.$accessToken];
        $res = requestPost($api, $body, $header);
        $resArr = json_decode($res, true);
        return $resArr;
    }
}