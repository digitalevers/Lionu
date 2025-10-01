<?php
/**
 * SEM搜索投放开发者模块
 */
namespace App\Controllers;

use App\Libraries\MarketingApi360\Account as Account360;
use App\Libraries\Tools\RedisTool as RedisTool;


class Semdev extends NeedloginController{
   
    /**
     * 普通版只能添加一个帐号
     */
    public function validCommercialVersion(){
        $uid = $this->uid;
        //媒体名称
        $media = $this->request->getPost('channel', 'trim|xss_clean|strip_tags', '');
        $sql = "SELECT * FROM `u_channel` WHERE ename=?";
        $values = [$media];
        $db = \Config\Database::connect();
        $mediaInfo = $db->query($sql, $values)->getRowArray();
        $sql = "SELECT * FROM `dev_media_account` WHERE channel_id=? AND uid=?";
        $values = [$mediaInfo['id'], $uid];
        $res = $db->query($sql, $values)->getResultArray();

        if(count($res) > 1){
            exit(json_encode(['code' => 199,'msg' => '普通版只能添加一个帐号'], JSON_UNESCAPED_UNICODE));
        } else {
            exit(json_encode(['code' => 200,'msg' => 'ok','channel_id'=>$mediaInfo['id']], JSON_UNESCAPED_UNICODE));
        }
    }
    
    /**
     * 获取所有开发者帐号
     */
    public function getAuthAccounts(){
        $uid = $this->uid;
        $sql = "SELECT * FROM `dev_media_account` WHERE uid=?";
        $values = [$uid];
        $db = \Config\Database::connect();
        $res = $db->query($sql, $values)->getResultArray();
        //键名冲突 不覆盖前面 而是以数组的形式组织数据
//         $groupedData = array_reduce($originalData, function($carry, $item) {
//             $carry[$item['channel']][] = $item;
//             return $carry;
//         }, []);
        
        $res = array_column($res, null, 'channel_id');
        //获取所有媒体数据
        $sql = "SELECT * FROM `u_channel` WHERE 1=1";
        $channels = $db->query($sql)->getResultArray();
        $channels = array_column($channels, null, 'id');
        
        foreach ($res as $k=>$v){
            unset($v['password']);
            unset($v['accessToken']);
            $res[$channels[$k]['ename']][] = $v;
            unset($res[$k]);
        }
        exit(json_encode(['code' => 200,'msg' => 'ok','data'=>$res], JSON_UNESCAPED_UNICODE));
    }
    
    /**
     * 提交 Microsoft 应用 appId secret
     * 通过回调页面 OAuth2.0授权获取accessToken和refreshToken
     */
    public function authBing(){
        $uid = $this->uid;
        $channelId = $this->request->getPost('channel_id', 'trim|intval', 0);
        $devkey = $this->request->getPost('devkey', 'trim|xss_clean|strip_tags', '');
        $aid = $this->request->getPost('aid', 'trim|xss_clean|strip_tags', '');
        $appId = $this->request->getPost('appId', 'trim|xss_clean|strip_tags', '');
        $secret = $this->request->getPost('secret', 'trim|xss_clean|strip_tags', '');
        
        //验证时键名需要与POST键名保持一致
        if (! $this->validate([
            'channel_id' => ['required', 'greater_than[0]'],
            'devkey' => ['required'],
            'aid' => ['required'],
            'appId' => ['required'],
            'secret' => ['required']
        ])) {
            //校验失败
            exit(json_encode(['code' => 199,'msg' => 'fail'], JSON_UNESCAPED_UNICODE));
        }
        $db = \Config\Database::connect();
        $sql = "SELECT COUNT(*) as _count FROM `dev_media_account` WHERE channel_id=? AND uid=?";
        $values = [$channelId, $uid];
        $count = $db->query($sql, $values)->getRowArray()['_count'];
        if(intval($count) > 0){
            exit(json_encode(['code' => 199,'msg' => '普通版只能添加一个帐号'], JSON_UNESCAPED_UNICODE));
        }
        
        //新增bing帐户记录
        $now = time();
        $auth_time  = date('Y-m-d H:i:s', $now);
        $sql = "INSERT INTO `dev_media_account`(app_id, devkey, devsecret, channel_id, channel_ename, channel_cname, auth_time, uid, media_account_id) VALUES(?,?,?,?,?,?,?,?,?)";
        //$values = [$appId, $secret, $channelId, 'bing', '必应', $auth_time, $uid];
        $values = [
            'app_id'=>$appId,
            'devkey'=>$devkey,
            'devsecret'=>$secret,
            'channel_id'=>$channelId,
            'channel_ename'=>'bing',
            'channel_cname'=>'必应',
            'auth_time'=>$auth_time,
            'uid'=>$uid,
            'media_account_id'=>$aid
        ];
        
        $insertResult = $db->query($sql, array_values($values));
        $newId = $insertResult->connID->insert_id;
        if($newId > 0){
            //组装redirectURL 并返送给前端
            $url = $this->getMicrosoftAuthorizeUrl($appId, $channelId);
            _json(["code"=>200,"msg"=>"授权成功","data"=>["url"=>$url]]);
        } else {
            _json(["code"=>199,"msg"=>"保存失败"]);
        }
    }
    
    /**
     * 获取 Microsoft 应用授权URL
     * TODO 动态获取回调地址 redirect_url
     * @param string $appId
     * @return string
     */
    private function getMicrosoftAuthorizeUrl($appId = '', $channelId = ''){
        $redirect_url = urlencode('https://testapi.digitalevers.com/SemAuth/bing?channelId='.$channelId);
        $authorizeUrl = 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize?client_id='.$appId.'&response_type=code&redirect_uri='.$redirect_url.'&response_mode=query&scope=openid%20offline_access%20https%3A%2F%2Fads.microsoft.com%2Fmsads.manage&state='.$appId;
        return $authorizeUrl;
    }
    
    /**
     * 提交百度开发者  appId secretKey accessToken
     * 可直接填入accessToken 也可以回调页面 OAuth2.0授权获取accessToken和refreshToken
     */
    public function authBaidu(){
        $uid = $this->uid;
        $channelId = $this->request->getPost('channel_id', 'trim|intval', 0);
        $appId = $this->request->getPost('appId', 'trim|intval', 0);
        $secretKey = $this->request->getPost('secretKey', 'trim|xss_clean|strip_tags', '');
        $username = $this->request->getPost('username', 'trim|xss_clean|strip_tags', '');
        $accessToken = $this->request->getPost('accessToken', 'trim|xss_clean|strip_tags', '');
        
        //验证时键名需要与POST键名保持一致
        if (! $this->validate([
            'channel_id' => ['required', 'greater_than[0]'],
            //'appId' => ['required'],
            //'secretKey' => ['required'],
            'username' => ['required'],
            'accessToken' => ['required'],
        ])) {
            //校验失败
            exit(json_encode(['code' => 199,'msg' => 'fail'], JSON_UNESCAPED_UNICODE));
        }
        $db = \Config\Database::connect();
        $sql = "SELECT COUNT(*) as _count FROM `dev_media_account` WHERE channel_id=? AND uid=?";
        $values = [$channelId, $uid];
        $count = $db->query($sql, $values)->getRowArray()['_count'];
        if(intval($count) > 0){
            exit(json_encode(['code' => 199,'msg' => '普通版只能添加一个帐号'], JSON_UNESCAPED_UNICODE));
        }
        
        //新增记录
        $now = time();
        $auth_time  = date('Y-m-d H:i:s', $now);
        $expire_time = date('Y-m-d 00:00:00', $now + 3 * 30 * 24 * 3600);   //token 三个月后过期
        //$sql = "INSERT INTO `dev_media_account`(app_id, devsecret, username, channel_id, channel_ename, channel_cname, accessToken, expire_time, auth_time, uid) VALUES(?,?,?,?,?,?,?,?,?,?)";
        $sql = "INSERT INTO `dev_media_account`(username, channel_id, channel_ename, channel_cname, accessToken, expire_time, auth_time, uid) VALUES(?,?,?,?,?,?,?,?)";
        $values = [
            //'app_id'=>$appId,
            //'devsecret'=>$secretKey,
            'username'=>$username,
            'channel_id'=>$channelId,
            'channel_ename'=>'baidu',
            'channel_cname'=>'百度',
            'accessToken'=>$accessToken,
            'expire_time'=>$expire_time,
            'auth_time'=>$auth_time,
            'uid'=>$uid];
        
        $insertResult = $db->query($sql, array_values($values));
        $newId = $insertResult->connID->insert_id;
        if($newId > 0){
            $values['id'] = $newId;
            //添加帐号成功后推入redis队列
            $this->_pushAccountToRedis($values);
            exit(json_encode(["code"=>200,"msg"=>"授权成功"],JSON_UNESCAPED_UNICODE));
        } else {
            exit(json_encode(["code"=>199,"msg"=>"保存失败"],JSON_UNESCAPED_UNICODE));
        }
    }
    
    /**
     * 提交360开发者  key secret username password
     * 通过帐号授权获取 api请求token
     */
    public function auth360(){
        $uid = $this->uid;
        $channelId = $this->request->getPost('channel_id', 'trim|intval', 0);
        $key = $this->request->getPost('devkey', 'trim|xss_clean|strip_tags', '');
        $secret = $this->request->getPost('devsecret', 'trim|xss_clean|strip_tags', '');
        $username = $this->request->getPost('username', 'trim|xss_clean|strip_tags', '');
        $password = $this->request->getPost('password', 'trim|xss_clean|strip_tags', '');
        //验证时键名需要与POST键名保持一致
        if (! $this->validate([
            'channel_id' => ['required', 'greater_than[0]'],
            'devkey' => ['required'],
            'devsecret' => ['required'],
            'username' => ['required'],
            'password' => ['required']
        ])) {
            //校验失败
            exit(json_encode(['code' => 199,'msg' => 'fail'], JSON_UNESCAPED_UNICODE));
        }
        $db = \Config\Database::connect();
        $sql = "SELECT COUNT(*) as _count FROM `dev_media_account` WHERE channel_id=? AND uid=?";
        $values = [$channelId, $uid];
        $count = $db->query($sql, $values)->getRowArray()['_count'];
        if(intval($count) > 0){
            exit(json_encode(['code' => 199,'msg' => '普通版只能添加一个帐号'], JSON_UNESCAPED_UNICODE));
        }

        $md5 = md5($password);
        $mkApi360  = new Account360();
        $resArr = $mkApi360->clientLogin($key, $secret, $username, $md5);
        if(isset($resArr['accessToken']) && !empty($resArr['accessToken'])){
            //新增记录
            $now = time();
            $auth_time  = date('Y-m-d H:i:s', $now);
            $expire_time = date('Y-m-d H:i:s', $now + 10 * 3600);   //token 十小时后过期
            $sql = "INSERT INTO `dev_media_account`(devkey, devsecret, username, password, channel_id, channel_ename, channel_cname, accessToken, expire_time, auth_time, uid) VALUES(?,?,?,?,?,?,?,?,?,?,?)";
            $values = [
                'devkey'=>$key, 
                'devsecret'=>$secret, 
                'username'=>$username, 
                'password'=>$md5, 
                'channel_id'=>$channelId, 
                'channel_ename'=>'s360', 
                'channel_cname'=>'360', 
                'accessToken'=>$resArr['accessToken'], 
                'expire_time'=>$expire_time, 
                'auth_time'=>$auth_time, 
                'uid'=>$uid
            ];
            $insertResult = $db->query($sql, array_values($values));
            $newId = $insertResult->connID->insert_id;
            if($newId > 0){
                $values['id'] = $newId;
                //添加帐号成功后推入redis队列
                $this->_pushAccountToRedis($values);
                exit(json_encode(["code"=>200,"msg"=>"授权成功"],JSON_UNESCAPED_UNICODE));
            } else {
                exit(json_encode(["code"=>199,"msg"=>"保存失败"],JSON_UNESCAPED_UNICODE));
            }
        } else {
            exit(json_encode(["code"=>$resArr['failures'][0]['code'],"msg"=>$resArr['failures'][0]['message']],JSON_UNESCAPED_UNICODE));
        }
    }
    
    /**
     * 360开发者帐号重新授权
     */
    public function reAuth360(){
        $uid = $this->uid;
        $accountId = $this->request->getPost('account_id', 'trim|intval', 0);
        $channelId = $this->request->getPost('channel_id', 'trim|intval', 0);
        $password = $this->request->getPost('password', 'trim|xss_clean|strip_tags', '');
        
        //TODO 重构到模型中
        $db = \Config\Database::connect();
        $sql = "SELECT * FROM `dev_media_account` WHERE id=? AND channel_id=? AND uid=?";
        $values = [$accountId, $channelId, $uid];
        $accountInfo = $db->query($sql, $values)->getRowArray();
        if(count($accountInfo) <= 0){
            exit(json_encode(['code' => 199,'msg' => '帐号不存在'], JSON_UNESCAPED_UNICODE));
        } else {
            $key = $accountInfo['devkey'];
            $secret = $accountInfo['devsecret'];
            $username = $accountInfo['username'];
        }
        //重新请求accessToken
        $md5 = md5($password);
        $mkApi360  = new Account360();        
        $resArr = $mkApi360->clientLogin($key, $secret, $username, $md5);
        if(isset($resArr['accessToken']) && !empty($resArr['accessToken'])){
            //更新授权记录
            $now = time();
            $auth_time  = date('Y-m-d H:i:s', $now);
            $expire_time = date('Y-m-d H:i:s', $now + 10 * 3600);   //token 十小时后过期
            $sql = "UPDATE `dev_media_account` SET `password`=?, accessToken=?, expire_time=?, auth_time=? WHERE id=?";
            $values = [$md5, $resArr['accessToken'], $expire_time, $auth_time, $accountId];
            $updateResult = $db->query($sql, $values);
            $updateRes = $updateResult->connID->affected_rows;
            if($updateRes !== false){
                exit(json_encode(["code"=>200,"msg"=>"授权成功"],JSON_UNESCAPED_UNICODE));
            } else {
                exit(json_encode(["code"=>199,"msg"=>"保存失败"],JSON_UNESCAPED_UNICODE));
            }
        } else {
            exit(json_encode(["code"=>$resArr['failures'][0]['code'],"msg"=>$resArr['failures'][0]['message']],JSON_UNESCAPED_UNICODE));
        }
    }
    
 
    /**
     * 获取所有广告帐号信息
     * TODO 一次性批量查询
     */
    private function getAllAccounts($uid, $startDate, $endDate){
        try{
            $datas = [];
            $accountModel = model('MediaAccountModel');
            $accountReportModel = model('MediaAccountDayModel');
            $oldReports = $accountReportModel->getOldReports($uid, $startDate, $endDate);
            $accounts = $accountModel->getAccountsMap('uid='.$uid.' AND status>=0');
            //dump($accounts);
            //dump($oldReports);
            //exit;
            $flag = model('ConfigModel')->getApiFlag();
            if($flag > 0){
                $channels = model('ChannelModel')->getAllChannels();
                if(count($accounts) > 0){
                    foreach($accounts as $acc){
                        $acc['channel_cname'] = $channels[$acc['channel_id']]['channel_name'];
                        switch ($acc['channel_ename']){
                            case 's360':
                                $accounts[$acc['id']] = $accountModel->getAccountBase360($acc);
                                $oldReports[$acc['id']] = $accountReportModel->getAccountReport360($acc, $startDate, $endDate, $oldReports);
                                break;
                            case 'baidu':
                                $accounts[$acc['id']] = $accountModel->getAccountBaseBaidu($acc);
                                $oldReports[$acc['id']] = $accountReportModel->getAccountReportBaidu($acc, $startDate, $endDate, $oldReports);
                                break;
                            case 'bing':
                                $accounts[$acc['id']] = $accountModel->getAccountBaseBing($acc);
                                $oldReports[$acc['id']] = $accountReportModel->getAccountReportBing($acc, $startDate, $endDate, $oldReports);
                                break;
                            default:
                                break;
                        }
                        
                    }
                }
            }
            //汇总整理数据
            if(count($oldReports) > 0){
                foreach ($oldReports as $account_id=>$v){
                    $oldReports[$account_id] = $this->_handleReport($v);
                }
            }
            
            //dump($oldReports);
            //dump($accounts);
            //exit;
            
            $datas = [];
            if(count($accounts) > 0){
                foreach ($accounts as $accound_id=>$acc){
                    $acc['account_id'] = $acc['id'];
                    $temp['id'] = $acc['id'];
                    $temp['channel_id'] = $acc['channel_id'];
                    $temp['channel_ename'] = $acc['channel_ename'];
                    $temp['channel_cname'] = $acc['channel_cname'];
                    $temp['balance'] = $acc['balance'];
                    $temp['budget'] = $acc['budget'];
                    $temp['company_name'] = $acc['company_name'];
                    $temp['username'] = $acc['username'];
                    $temp['status'] = intval($acc['status']);
                    $temp['balance'] = ($temp['balance'] < 0) ? '-' : floatval($temp['balance']/100);
                    $temp['budget'] = ($temp['budget'] < 0) ? '-' : floatval($temp['budget']/100);
                    //dump($oldReports);
                    //exit;
                    $temp = array_merge($temp, $this->_initReport($oldReports, $acc, 'media_account_id'));
                    $datas[] = $temp;
                }
            }
            //exit(json_encode(["code"=>200,"msg"=>"ok","data"=>$datas],JSON_UNESCAPED_UNICODE));
            _json(["code"=>200,"msg"=>"ok","data"=>$datas],1);
        } catch (\Exception $e){
            _json(["code"=>199,"msg"=>$e->getMessage(),"data"=>[]]);
        }
    }
    
    /**
     * 获取所有推广计划
     */
    private function getAllCampaigns($uid, $startDate, $endDate){
        $uid = $this->uid;
        $accounts = model('MediaAccountModel')->getAccountsMap(['uid'=>$uid,'status'=>1]);
        $campaignModel = model('MediaCampaignModel');
        $campaigns = $campaignModel->getCampaigns($accounts);
        $campaignReportModel = model('MediaCampaignDayModel');
        $oldReports = $campaignReportModel->getOldReports($accounts, $startDate, $endDate);
        $flag = model('ConfigModel')->getApiFlag();
        if($flag > 0){
            foreach ($accounts as $account_id=>$account){
                switch ($account['channel_ename']){
                    case 's360':
                        $campaigns[$account_id] = $campaignModel->getCampaignBase360($account, $campaigns);
                        //若$oldReports按引用传值 可以直接修改该值 但是采取返回值的方式 让代码可读性更好
                        $oldReports[$account_id] = $campaignReportModel->getCampaignReport360($account, $startDate, $endDate, $oldReports, $campaigns[$account_id]);
                        //dump($oldReports[$account_id]);
                        //exit;
                        break;
                    case 'baidu':
                        $campaigns[$account_id] = $campaignModel->getCampaignBaseBaidu($account, $campaigns);
                        $oldReports[$account_id] = $campaignReportModel->getCampaignReportBaidu($account, $startDate, $endDate, $oldReports, $campaigns[$account_id]);
                        //dump($report);
                        //dump($oldReports);
                        break;
                    case 'bing':
                        $campaigns[$account_id] = $campaignModel->getCampaignBaseBing($account, $campaigns);
                        $oldReports[$account_id] = $campaignReportModel->getCampaignReportBing($account, $startDate, $endDate, $oldReports, $campaigns[$account_id]);
                        //dump($report);
                        //dump($oldReports);
                        break;
                    default:
                        break;
                }
            }
        }
        //dump($oldReports);
        //exit;
        //汇总整理数据
        if(count($oldReports) > 0){
            foreach ($oldReports as $account_id=>$v){
                $oldReports[$account_id] = $this->_handleReport($v);
            }
        }
        //dump($campaigns);
        //exit;
        //格式化输出
        $_campaigns = array_reduce($campaigns, function ($carry, $item) use ($accounts, $oldReports) {
            if(count($item) > 0){
                foreach ($item as &$camp){
                    $camp['budget'] = floatval($camp['budget']/100);
                    $camp['channel_cname'] = $accounts[$camp['account_id']]['channel_cname'];
                    $camp['channel_ename'] = $accounts[$camp['account_id']]['channel_ename'];
                    $camp['username'] = $accounts[$camp['account_id']]['username'];
                    $camp['campaignBidType'] = isset($camp['campaignBidType']) ? $this->switchBidType($camp['campaignBidType']) : 'cpc';
                    $camp['campaignBid'] = (isset($camp['campaignBid']) && $camp['campaignBid'] > 0) ? floatval($camp['campaignBid']/100) : -1;
                    $camp['campaignOcpcBid'] = (isset($camp['campaignOcpcBid']) && $camp['campaignOcpcBid'] > 0) ? floatval($camp['campaignOcpcBid']/100) : -1;
                    $camp['mCampaignBidType'] = isset($camp['mCampaignBidType']) ? $this->switchBidType($camp['mCampaignBidType']) : 'cpc';
                    $camp['mCampaignBid'] = (isset($camp['mCampaignBid']) && $camp['mCampaignBid'] > 0) ? floatval($camp['mCampaignBid']/100) : -1;
                    $camp['mCampaignOcpcBid'] = (isset($camp['mCampaignOcpcBid']) && $camp['mCampaignOcpcBid'] > 0) ? floatval($camp['mCampaignOcpcBid']/100) : -1;
                    
                    $camp += $this->_initReport($oldReports, $camp, 'media_campaignid');
                }
            }
            return array_merge($carry, $item);
        }, []);
        
        _json(["code"=>200,"msg"=>"ok","data"=>$_campaigns]);
    }
    
    /**
     * 获取所有广告组信息
     */
    private function getAllAdvGroups($uid, $startDate, $endDate){
        $uid = $this->uid;
        $accounts = model('MediaAccountModel')->getAccountsMap(['uid'=>$uid, 'status'=>1]);
        $campaigns = model('MediaCampaignModel')->getCampaigns($accounts);
        //dump($campaigns);
        //exit;
        $advGroupModel = model('MediaAdvGroupModel');
        //在帐户数据上挂载推广计划信息
        if(count($accounts) > 0){
            foreach ($accounts as $account_id=>&$info){
                $info['campaigns'] = isset($campaigns[$account_id]) ? $campaigns[$account_id] : [];
            }
        }
        //获取数据库中原始广告组信息  以内部帐户id account_id作为 array key
        $advGroups = $advGroupModel->getAdvGroups($accounts);
        $advGroupDayModel = model('MediaAdvGroupDayModel');
        $oldReports = $advGroupDayModel->getOldReports($accounts, $startDate, $endDate);
        //dump($advGroups);
        //dump($oldReports);
        //exit;
        
        $flag = model('ConfigModel')->getApiFlag();
        if($flag > 0){
            foreach ($accounts as $account_id=>$account){
                switch ($account['channel_ename']){
                    case 's360':
                        $advGroups[$account_id] = $advGroupModel->getAdvGroupBase360($account, $advGroups);
                        $oldReports[$account_id] = $advGroupDayModel->getGroupReport360($account, $startDate, $endDate, $oldReports, $advGroups[$account_id]);
                        //dump($advGroups);
                        //dump($oldReports);
                        //exit;
                        break;
                    case 'baidu':
                        $advGroups[$account_id] = $advGroupModel->getAdvGroupBaseBaidu($account, $advGroups);
                        $oldReports[$account_id] = $advGroupDayModel->getGroupReportBaidu($account, $startDate, $endDate, $oldReports, $advGroups[$account_id]);
                        break;
                    case 'bing':
                        $advGroups[$account_id] = $advGroupModel->getAdvGroupBaseBing($account, $advGroups);
                        $oldReports[$account_id] = $advGroupDayModel->getGroupReportBing($account, $startDate, $endDate, $oldReports, $advGroups[$account_id]);
                        break;
                    default:
                        break;
                }
            }
        }
        //汇总整理数据
        if(count($oldReports) > 0){
            foreach ($oldReports as $account_id=>$v){
                $oldReports[$account_id] = $this->_handleReport($v);
            }
        }
        
        //挂载 campaign的 campaignBidType 出价方式等数据
        $campaigns = model('MediaCampaignModel')->getCampaigns($accounts, 'id');
        $advGroups = array_reduce($advGroups, function ($carry, $item) use ($accounts, $oldReports, $campaigns) {
            if(count($item) > 0){
                foreach ($item as &$group){
                    $group['price'] = floatval($group['price']/100);
                    $group['campaignBidType'] = intval($campaigns[$group['campaign_id']][0]['campaignBidType']);
                    $group['channel_cname'] = $accounts[$group['account_id']]['channel_cname'];
                    $group['channel_ename'] = $accounts[$group['account_id']]['channel_ename'];
                    $group['username'] = $accounts[$group['account_id']]['username'];
                    $group += $this->_initReport($oldReports, $group, 'media_advgroupid');
                }
            }
            return array_merge($carry, $item);
        }, []);
        _json(["code"=>200,"msg"=>"ok","data"=>$advGroups]); 
    }
    
    /**
     * 获取所有广告信息
     * 有些媒体只有三层分类 所以可能没有  "广告" 这个维度
     */
    private function getAllAdvs($uid, $startDate, $endDate){
        $uid = $this->uid;
        $adv = [];
        _json(["code"=>200,"msg"=>"ok","data"=>$adv]); 
    }
    
    /**
     * 获取所有创意信息
     */
    private function getAllCreatives($uid, $startDate, $endDate){
        $uid = $this->uid;
        $creatives = [];
        $accounts = model('MediaAccountModel')->getAccountsMap(['uid'=>$uid, 'status'=>1]);
        $advgroups = model('MediaAdvGroupModel')->getAdvGroups($accounts);
        //在帐户数据上挂载推广组信息
        if(count($accounts) > 0){
            foreach ($accounts as $account_id=>&$info){
                $info['advgroups'] = isset($advgroups[$account_id]) ? $advgroups[$account_id] : [];
            }
        }
        //获取数据库中原始创意信息  以内部帐户id account_id作为 array key
        $creativeModel = model('MediaCreativeModel');
        $creatives = $creativeModel->getCreatives($accounts);
        $creativeReportModel = model('MediaCreativeDayModel');
        $oldReports = $creativeReportModel->getOldReports($accounts, $startDate, $endDate);
        //dump($accounts);
        //dump($creatives);
        //exit;
        $flag = model('ConfigModel')->getApiFlag();
        if($flag > 0){
            foreach ($accounts as $account_id=>$account){
                switch ($account['channel_ename']){
                    case 's360':
                        $creatives[$account_id] = $creativeModel->getCreativeBase360($account, $creatives);
                        $oldReports[$account_id] = $creativeReportModel->getCreativeReport360($account, $startDate, $endDate, $oldReports, $creatives[$account_id]);
                        break;
                    case 'baidu':
                        $creatives[$account_id] = $creativeModel->getCreativeBaseBaidu($account, $creatives);
                        $oldReports[$account_id] = $creativeReportModel->getCreativeReportBaidu($account, $startDate, $endDate, $oldReports, $creatives[$account_id]);
                        break;
                    case 'bing':
                        $creatives[$account_id] = $creativeModel->getCreativeBaseBing($account, $creatives);
                        $oldReports[$account_id] = $creativeReportModel->getCreativeReportBing($account, $startDate, $endDate, $oldReports, $creatives[$account_id]);
                        break;
                    default:
                        break;
                }
            }
        }
        //汇总整理数据
        if(count($oldReports) > 0){
            foreach ($oldReports as $account_id=>$v){
                $oldReports[$account_id] = $this->_handleReport($v, 0);
            }
        }
        $newCreatives = array_reduce($creatives, function ($carry, $item) use ($accounts, $oldReports) {
            if(count($item) > 0){
                foreach ($item as &$creative){
                    $creative['channel_cname'] = $accounts[$creative['account_id']]['channel_cname'];
                    $creative['channel_ename'] = $accounts[$creative['account_id']]['channel_ename'];
                    $creative['username'] = $accounts[$creative['account_id']]['username'];
                    $creative += $this->_initReport($oldReports, $creative, 'media_creativeid');
                }
            }
            return array_merge($carry, $item);
        }, []);
        _json(["code"=>200,"msg"=>"ok","data"=>$newCreatives]);
    }
    
    public function getAllDimensionData(){
        $uid = $this->uid;
        $startDate = $this->request->getPost('startDate', 'trim|xss_clean|strip_tags', '');
        $endDate = $this->request->getPost('endDate', 'trim|xss_clean|strip_tags', '');
        $startDate = empty($startDate) ? date('Y-m-d',time()) : $startDate;
        $endDate = empty($endDate) ? date('Y-m-d',time()) : $endDate;
        $dimension = $this->request->getPost('dimension', 'intval', 1);
        
        switch ($dimension){
            case 1:
                $this->getAllAccounts($uid, $startDate, $endDate);
                break;
            case 2:
                $this->getAllCampaigns($uid, $startDate, $endDate);
                break;
            case 3:
                $this->getAllAdvGroups($uid, $startDate, $endDate);
                break;
            case 4:
                $this->getAllAdvs($uid, $startDate, $endDate);
                break;
            case 5:
                $this->getAllCreatives($uid, $startDate, $endDate);
                break;
            default:
                $this->getAllAccounts($uid, $startDate, $endDate);
                break;
        }
    }
    
    /**
     * 检查数组中某个键是否存在
     * 存在返回该键值
     * 不存在则返回tpl模板数据
     * @param array $report
     * @param unknown $camp
     * @return number[]|unknown
     */
    private function _initReport($report = [], $needle = [], $key = ''){
        $tpl = [
            "pc_consume" => 0,
            "pc_shows" => 0,
            "pc_clicks" => 0,
            "pc_converts" => 0,
            "pc_convertsCost" => 0,
            "m_consume" => 0,
            "m_shows" => 0,
            "m_clicks" => 0,
            "m_converts" => 0,
            "m_convertsCost" => 0,
            "consume" => 0,
            "shows" => 0,
            "clicks" => 0,
            "converts" => 0,
            "convertsCost" => 0
        ];
        return isset($report[$needle['account_id']][$needle[$key]]) ? $report[$needle['account_id']][$needle[$key]] : $tpl;
    }
    
    /**
     * 根据前端需求重新组织数据
     * @param array $report
     * 1. 按日期汇总累计点击消耗等数据
     */
    private function _handleReport($report = [], $hasConvert = 1){
        $computerReport = isset($report['computer']) ? $report['computer'] : [];
        $mobileReport = isset($report['mobile']) ? $report['mobile'] : [];
        
        $newReport = [];
        if(count($report) > 0){
            //每个数据项对所有日期汇总求和
            if(count($computerReport) > 0){
                foreach ($computerReport as $someID=>$v){       //键名为 推广计划ID或广告组ID或者创意ID
                    $newReport[$someID]['pc_consume'] = array_sum(array_column($v, 'consume'));
                    $newReport[$someID]['pc_shows'] = array_sum(array_column($v, 'shows'));
                    $newReport[$someID]['pc_clicks'] = array_sum(array_column($v, 'clicks'));
                    if($hasConvert > 0){
                        $newReport[$someID]['pc_converts'] = array_sum(array_column($v, 'converts'));
                    } else {
                        $newReport[$someID]['pc_converts'] = '-';
                    }
                }
            }
            if(count($mobileReport) > 0){
                foreach ($mobileReport as $someID=>$v){
                    $newReport[$someID]['m_consume'] = array_sum(array_column($v, 'consume'));
                    $newReport[$someID]['m_shows'] = array_sum(array_column($v, 'shows'));
                    $newReport[$someID]['m_clicks'] = array_sum(array_column($v, 'clicks'));
                    if($hasConvert > 0){
                        $newReport[$someID]['m_converts'] = array_sum(array_column($v, 'converts'));
                    } else {
                        $newReport[$someID]['m_converts'] = '-';
                    }
                }
            }
        }
        //dump($newReport);
        
        if(count($newReport) > 0){
            foreach ($newReport as $someID=>&$v){
                $v['consume'] = (isset($v['pc_consume']) ? $v['pc_consume'] : 0) + (isset($v['m_consume']) ? $v['m_consume'] : 0);
                $v['shows'] = (isset($v['pc_shows']) ? $v['pc_shows'] : 0) + (isset($v['m_shows']) ? $v['m_shows'] : 0);
                $v['clicks'] = (isset($v['pc_clicks']) ? $v['pc_clicks'] : 0) + (isset($v['m_clicks']) ? $v['m_clicks'] : 0);
                if($hasConvert > 0){
                    $v['converts'] = (isset($v['pc_converts']) ? $v['pc_converts'] : 0) + (isset($v['m_converts']) ? $v['m_converts'] : 0);
                    $v['convertsCost'] = $v['converts'] == 0 ? '-' : floatval($v['consume'] / $v['converts'] / 100);
                    $v['pc_convertsCost'] = (!isset($v['pc_converts']) || $v['pc_converts'] == 0) ? '-' : floatval($v['pc_consume'] / $v['pc_converts'] / 100);
                    $v['m_convertsCost'] = (!isset($v['m_converts']) || $v['m_converts'] == 0) ? '-' : floatval($v['m_consume'] / $v['m_converts'] / 100);
                } else {
                    $v['converts'] = '-';
                    $v['convertsCost'] = '-';
                    $v['pc_convertsCost'] = '-';
                    $v['m_convertsCost'] = '-';
                }
                $v['pc_consume'] = isset($v['pc_consume']) ? floatval($v['pc_consume']/100) : 0;
                $v['m_consume'] = isset($v['m_consume']) ? floatval($v['m_consume']/100) : 0;
                $v['consume'] = floatval($v['consume']/100);
            }
        }
        return $newReport;
    }
    
    /**
     * 将帐户信息发布到 Redis 的频道
     * 其他渠道的添加帐户的时候就可以将帐户信息推入redis
     * 但唯独bing比较特殊 它是在web授权之后才拿到 accessToken 所以要在授权完成才推入redis
     */
    private function _pushAccountToRedis($account){
        //初始化一些必要的变量
        //$account['username'] = isset($account['username']) ? $account['username'] : '';
        $account['balance'] = 0;
        $account['budget'] = 0;
        $account['company_name'] = '';
        $account['media_account_id'] = '';
        $account['status'] = 0;
        $account['media_status'] = '';
        $tool = new RedisTool();
        $res = $tool->pub($account);
        if(!$res){
            log_message('error','_pushAccountToRedis fail.The account id is'.$account['id']);
        }
    }
    

  
    
}