<?php
namespace App\Controllers;

use App\Libraries\MarketingApiBing\Account as AccountBing;
use App\Libraries\Tools\RedisTool as RedisTool;

class SemAuth extends BaseController
{
    /**
     * 百度marketing api OAuth2.0授权回调地址
     * https://testapi.digitalevers.com/SemAuth/baidu
     * TODO channel_id 硬编码
     */
    public function baidu(){
        $appId = $this->request->getGet('appId', 'trim|xss_clean|strip_tags', '');
        $authCode = $this->request->getGet('authCode', 'trim|xss_clean|strip_tags', '');
        $state = $this->request->getGet('state', 'trim|xss_clean|strip_tags', '');
        $timestamp = $this->request->getGet('timestamp', 'trim|xss_clean|strip_tags', '');
        $userId = $this->request->getGet('userId', 'trim|xss_clean|strip_tags', '');
        $signature = $this->request->getGet('signature', 'trim|xss_clean|strip_tags', '');
        //验签
        $params = [
            'appId'=>$appId,
            'authCode'=>$authCode,
            'state'=>$state,
            'timestamp'=>$timestamp,
            'userId'=>$userId
        ];
        //查找secretKey
        $db = \Config\Database::connect();
        $sql = "SELECT devsecret FROM `dev_media_account` WHERE channel_id=3 AND app_id=?";
        $values = [$appId];
        $appInfo = $db->query($sql, $values)->getRowArray();
        if(count($appInfo) <= 0){
            exit(json_encode(['code' => 199,'msg' => '未找到相关应用'], JSON_UNESCAPED_UNICODE));
        }
        $json = json_encode($params);
        $secretKey = $appInfo['devsecret'];
        $sign = $this->_baiduAESignature($json, $secretKey);
        if($sign !== $signature){
            exit(json_encode(['code' => 198,'msg' => '验签失败'], JSON_UNESCAPED_UNICODE));
        } else {
            //验签成功 使用authCode获取accessToken
            //TODO
        }
    }

    
    /**
     * baidu OAuth2.0 AES加密获取签名
     * 官方由Java实现  这里是对应的php版本
     * 注意：json中不能包含多余的空格 否则加密结果会不同
     */
    private function _baiduAESignature($json, $secret){
        $iv = str_repeat("\0", 16);
        $bytes = base64_encode($json);
        $secret = substr($secret, 0, 16);
        
        $blockSize = 16;
        $padding = $blockSize - (strlen($bytes) % $blockSize);
        $bytes .= str_repeat("\0", $padding);
        
        $encrypt = openssl_encrypt($bytes, 'AES-128-CBC', $secret, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);
        $result = strtoupper(bin2Hex($encrypt));
        return $result;
    }
    
    /**
     * 必应marketing api OAuth2.0授权回调地址
     * https://testapi.digitalevers.com/SemAuth/bing
     */
    public function bing(){
        $channelId = $this->request->getGet('channelId', 'trim|xss_clean|strip_tags', '');
        $code = $this->request->getGet('code', 'trim|xss_clean|strip_tags', '');
        //appid附带在state参数上 便于查找secret
        $appId = $this->request->getGet('state', 'trim|xss_clean|strip_tags', '');
        $accountModel = model('MediaAccountModel');
        $accounts = $accountModel->getAccount($appId, $channelId);
        if(count($accounts) > 0){
            $account = $accounts[0];
            $secret = $account['devsecret'];
            //请求 Microsoft accessToken和refreshToken
            $bing = new AccountBing;
            $resArr = $bing->getToken($appId, $code, $channelId, $secret);
            //dump($resArr);
            if(isset($resArr['error']) && !empty($resArr['error'])){
                _json(['code' => 198,'msg' => $resArr['error']]);
            } else {
                $update = [
                    'id'=>$account['id'],
                    'accessToken'=>$resArr['access_token'],
                    'refreshToken'=>$resArr['refresh_token'],
                    'expire_time'=>date('Y-m-d H:i:s',time() + $resArr['expires_in']),       //accessToken有效期一般为3599s
                    'status'=>1
                ];
                $accountModel->updateAccount($update);
                //授权完成后推入redis队列
                $account = array_merge($account, $update);
                $this->_pushAccountToRedis($account);
                _json(['code' => 200,'msg' => 'ok']);
            }
        } else {
            _json(['code' => 199,'msg' => '参数错误']);
        }
    }
    
    /**
     * 将帐户信息发布到 Redis 的频道
     * 其他渠道的添加帐户的时候就可以将帐户信息推入redis
     * 但唯独bing比较特殊 它是在web授权之后才拿到 accessToken 所以要在授权完成才推入redis
     */
    private function _pushAccountToRedis($account){
        $tool = new RedisTool();
        $res = $tool->pub($account);
        if(!$res){
            log_message('error','_pushAccountToRedis fail.The account id is'.$account['id']);
        }
    }
    
}