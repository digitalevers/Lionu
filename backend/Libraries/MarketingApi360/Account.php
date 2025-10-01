<?php
namespace App\Libraries\MarketingApi360;

defined('FCPATH') OR exit('No direct script access allowed');
/**
 * 360 Marketing api封装调用库
 * @author Administrator
 *
 */
class Account {
    
    /**
     * 客户登录 请求accessToken
     * @param unknown $account_id
     * @param unknown $channel_id
     * @param unknown $password
     * @return mixed
     */
    public function clientLogin($key, $secret, $username, $password_md5) {
        $iv = substr($secret, 16, 16);
        $encrypt = openssl_encrypt($password_md5, 'AES-128-CBC', $secret, OPENSSL_RAW_DATA, $iv);
        $data = strtolower(bin2hex($encrypt));
        $encryptedPwd = substr($data, 0, 64);
        
        //发送POST请求token
        $api = 'https://api.e.360.cn/uc/account/clientLogin';
        $body = 'username='.$username.'&passwd='.$encryptedPwd;         //以url参数拼接的方式传递
        $header = ['Content-Type: application/x-www-form-urlencoded;charset=utf-8','apiKey:'.$key];
        $res = requestPost($api, $body, $header);
        $resArr = json_decode($res, true);
        return $resArr;
    }
    
    
    /**
     * 获取360广告投放帐号信息
     */
    public function getInfo($key, $accessToken){
        $api = 'https://api.e.360.cn/uc/account/getInfo';
        $body = '';         //以url参数拼接的方式传递
        $header = ['Content-Type: application/x-www-form-urlencoded;charset=utf-8','apiKey:'.$key, 'accessToken:'.$accessToken];
        $res = requestPost($api, $body, $header);
        $resArr = json_decode($res, true);
        return $resArr;
    }
    
    
    /**
     * 设置推广账号的每日预算
     */
    public function setDayBudget($key, $accessToken, $budget){
        $api = 'https://api.e.360.cn/dianjing/account/updateBudget';
        $body = 'budget='.$budget.'&platType=search';
        $header = ['Content-Type: application/x-www-form-urlencoded;charset=utf-8','apiKey:'.$key, 'accessToken:'.$accessToken];
        $res = requestPost($api, $body, $header);
        $resArr = json_decode($res, true);
        return $resArr;
    }
}