<?php
namespace App\Libraries\MarketingApiBing;

defined('FCPATH') OR exit('No direct script access allowed');
/**
 * Microsoft Bing Marketing api封装调用库
 * @author Administrator
 *
 */
class Account {
    
    /**
     * 请求 Microsoft accessToken和refreshToken
     * @param unknown $appId        Micrsoft应用(客户端)ID
     * @param unknown $code         被redirect_uri接受的code码
     * @param unknown $channelId    系统内部约定的渠道id
     * @param unknown $secret       Micrsoft应用客户端密码值
     */
    public function getToken($appId, $code, $channelId, $secret){
        $api = 'https://login.microsoftonline.com/common/oauth2/v2.0/token';
        $bodyArr = [
            'client_id'=>$appId,
            'scope'=>'https://ads.microsoft.com/msads.manage',
            'code'=>$code,
            'redirect_uri'=>'https://testapi.digitalevers.com/SemAuth/bing?channelId='.$channelId,
            'grant_type'=>'authorization_code',
            'client_secret'=>$secret,
        ];
        $body = http_build_query($bodyArr);                                 //以url参数拼接的方式传递
        $header = ['Content-Type: application/x-www-form-urlencoded;charset=utf-8'];
        $res = requestPost($api, $body, $header);
        $resArr = json_decode($res, true);
        return $resArr;
    }
}