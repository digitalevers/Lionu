<?php
namespace App\Libraries\MarketingApiBaidu\SEM\AdvManage;

class AdvCreative{
    
    /**
     * 获取百度投放后台的  "创意" 模块
     * @param unknown $userName     帐户登录名
     * @param unknown $accessToken  授权token
     * @param int     $idType       查询层级  5-单元ID 7-创意ID
     * @param array   $ids          授权token
     *
     * @return mixed
     */
    public function getAllCreatives($userName, $accessToken, $idType, $ids){
        $api = 'https://api.baidu.com/json/sms/service/CreativeService/getCreative';
        $bodyArr = [
            "header"=>["accessToken"=>$accessToken,"userName"=>$userName],
            "body"=>[
                "creativeFields"=>[
                    "creativeId", "adgroupId", "title", "description1", "pcDestinationUrl", "pcDisplayUrl",
                    "mobileDestinationUrl", "mobileDisplayUrl", "status", "createTime"
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
     * 更新 Baidu 创意
     * @param unknown $userName     帐户登录名
     * @param unknown $accessToken  授权token
     * @param unknown $body         更新的创意信息
     * @return mixed
     */
    public function updateCreatives($userName, $accessToken, $body){
        $api = 'https://api.baidu.com/json/sms/service/CreativeService/updateCreative';
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