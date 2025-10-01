<?php
/**
 * 推广计划数据模型
 */
namespace App\Models;

use App\Libraries\MarketingApi360\SEM\AdvManage\AdvCampaign as AdvCampaign360;
use App\Libraries\MarketingApiBaidu\SEM\AdvManage\AdvCampaign as AdvCampaignBaidu;
use App\Libraries\MarketingApiBing\SEM\AdvManage\AdvCampaign as AdvCampaignBing;
use CodeIgniter\CLI\CLI;
use App\Libraries\MarketingApi360\SEM\AdvOthers\Ocpc;

class MediaCampaignModel extends \CodeIgniter\Model
{
    protected $table = 'dev_media_campaign';
    
    //关闭字段保护 使得不填写 $allowedFields 也能插入数据
    //protected $protectFields = false;
    //允许更新的字段
    protected $allowedFields = [
        'account_id',
        'media_campaignid',
        'campaign_name',
        'budget',
        'region',
        'schedule',
        'startDate',
        'endDate',
        'status',
        'media_status',
        'addTime',
        'updateTime',
        'device',
        'campaignBid',
        'campaignBidType',
        'campaignOcpcBid',
        'campaignOcpcBidType',
        'mCampaignBid',
        'mCampaignBidType',
        'mCampaignOcpcBid',
        'mCampaignOcpcBidType',
        'ad_ocpc_id',
        'm_ad_ocpc_id',
    ];
    
    //需要更新的字段
    protected $needUpdateFields = [
        'campaign_name',
        'budget',
        'region',
        'schedule',
        'startDate',
        'endDate',
        'status',
        'media_status',
        'updateTime',
        'device',
        'campaignBid',
        'campaignBidType',
        'campaignOcpcBid',
        'campaignOcpcBidType',
        'mCampaignBid',
        'mCampaignBidType',
        'mCampaignOcpcBid',
        'mCampaignOcpcBidType',
        'ad_ocpc_id',
        'm_ad_ocpc_id',
    ];
    
    /* protected $validationRules = [
        'username' => 'required|min_length[3]|is_unique[accounts.username]',
        'balance' => 'numeric',
        'status' => 'in_list[-7,-6,-5,-4,-3,-2,-1,0,1,2]',
        'company_name' => 'permit_empty|max_length[100]'
    ];*/
    
    /**
     * 查找所有推广计划并以 account_id作为键名组装数组返回
     * @param string $where
     * @return array
     */
    public function getCampaigns($accounts, $key = 'account_id'){
        $res = [];
        $accountIds = array_keys($accounts);
        if(count($accountIds) > 0){
            $_aids = implode(',', $accountIds);
            $allCampaigns = $this->where('account_id IN('.$_aids.')')->findAll();
            //dump($allCampaigns);
            //exit;
            if(count($allCampaigns) > 0){
                $res = groupByKey($allCampaigns, $key);
            }
        }
        return $res;
    }
    
    /**
     * 获取360推广计划基本信息
     * @param array $accInfo 数据库读取的广告帐户信息
     * @param array $campaigns 以 account_id 为key的推广计划信息数组
     * 
     * 尽量避免使用 ON DUPLICATE KEY UPDATE 高并发下会有性能问题
     */
    public function getCampaignBase360($accInfo = [], $campaigns = []){
        $campaign = isset($campaigns[$accInfo['id']]) ? $campaigns[$accInfo['id']] : [];
        $oldCampaigns = array_column($campaign, null, 'media_campaignid');
        do {
            //获取推广计划id列表
            $advCampaign360  = new AdvCampaign360();
            $campaignIDs = $advCampaign360->getAllCampaigns($accInfo['devkey'], $accInfo['accessToken']);
            if(isset($campaignIDs['failures'])){
                break;
            }
            //使用推广计划id获取明细
            $newCampaigns = $advCampaign360->getCampaignDetail($accInfo['devkey'], $accInfo['accessToken'], $campaignIDs['campaignIdList']);
            if(isset($newCampaigns['failures'])){
                break;
            }
            $newCampaigns = $newCampaigns['campaignList'];
            if(count($newCampaigns) > 0){
                $ocpcCampaignsInfo = $this->_360GetCampaignOcpcInfo($accInfo);
                foreach ($newCampaigns as $v){
                    $insertData = [
                        'account_id'=>$accInfo['id'],
                        'media_campaignid'=>$v['id'],
                        'campaign_name'=>$v['name'],
                        'budget'=>$v['budget']*100,
                        'region'=>$v['region'],
                        'schedule'=>json_encode($v['schedule']),
                        'startDate'=>$v['startDate'],
                        'endDate'=>$v['endDate'],
                        'status'=>$this->_360CampaignStatusMap($v['status'])['statusCode'],
                        'media_status'=>$this->_360CampaignStatusMap($v['status'])['statusTxt'],
                        'addTime'=>$v['addTime'],
                        'updateTime'=>$v['updateTime'],
                        'device'=>$v['device'],
                    ];
                    $insertData += $this->_360GetCampaignBidInfo($v, $ocpcCampaignsInfo);
                    //dump($insertData);
                    //dump($oldCampaigns);
                    //exit;
                    $decide = $this->insertOrUpdateOrNothing($insertData, $oldCampaigns);
                    if($decide['code'] == 2){
                        $insertID = $this->insert($insertData, true);
                        if($insertID){
                            $insertData['id'] = $insertID;
                            $oldCampaigns[$insertData['media_campaignid']] = $insertData;
                        } else {
                            //TODO 写入失败 记录日志
                            break;
                        }
                    } elseif($decide['code'] == 1){
                        $updateRes = $this->save($decide['update']);
                        if($updateRes > 0){
                            $insertData['id'] = $decide['update']['id'];
                            $oldCampaigns[$insertData['media_campaignid']] = $insertData;
                        } else {
                            //TODO 更新失败 记录日志
                            break;
                        }
                    }
                }
            }
        } while(false);
        
        return array_values($oldCampaigns);
    }
    
    /**
     * 获取百度推广计划基本信息
     * @param array $accInfo   数据库读取的广告帐户信息
     * @param array $campaigns 以 account_id 为key的推广计划信息数组
     */
    public function getCampaignBaseBaidu($accInfo = [], $campaigns = []){
        $campaign = isset($campaigns[$accInfo['id']]) ? $campaigns[$accInfo['id']] : [];
        $oldCampaigns = array_column($campaign, null, 'media_campaignid');
        do{
            //获取百度投放"方案"模块(对应推广计划)信息
            $advCampaignBaidu = new AdvCampaignBaidu();
            $advCampaigns = $advCampaignBaidu->getAllCampaigns($accInfo['username'], $accInfo['accessToken']);
            if(count($advCampaigns['header']['failures']) > 0){
                break;
            }
            $newCampaigns = $advCampaigns['body']['data'];
            //$newCampaigns = array_column($data, 'campaignId');
            //dump($newCampaigns);
            if(count($newCampaigns) > 0){
                foreach ($newCampaigns as $v){
                    $insertData = [
                        'account_id'=>$accInfo['id'],
                        'media_campaignid'=>$v['campaignId'],
                        'campaign_name'=>$v['campaignName'],
                        'budget'=>$v['budget']*100,
                        'region'=>isset($v['regionTarget']) ? json_encode($v['regionTarget']) : '',
                        'schedule'=>isset($v['schedule']) ? json_encode($v['schedule']) : '',
                        'status'=>$this->_baiduCampaignMap($v['status'], 'status')['statusCode'],
                        'media_status'=>$this->_baiduCampaignMap($v['status'], 'status')['statusTxt'],
                        'addTime'=>$v['createTime'],
                        'device'=>$this->_baiduCampaignMap($v['equipmentType'], 'device'),
                        'negativeWords'=>json_encode($v['negativeWords']),
                        'exactNegativeWords'=>json_encode($v['exactNegativeWords']),
                        'campaignBidType'=>$v['campaignBidType'],
                        'campaignBid'=>intval($v['campaignBid'] * 100),
                        'campaignOcpcBidType'=>$v['campaignOcpcBidType'],
                        'campaignOcpcBid'=>isset($v['campaignOcpcBid']) ? intval($v['campaignOcpcBid'] * 100) : 0
                    ];
                    $decide = $this->insertOrUpdateOrNothing($insertData, $oldCampaigns);
                    if($decide['code'] == 2){
                        $insertID = $this->insert($insertData, true);
                        if($insertID){
                            $insertData['id'] = $insertID;
                            $oldCampaigns[$insertData['media_campaignid']] = $insertData;
                        } else {
                            //TODO 写入失败 记录日志
                            break;
                        }
                    } elseif($decide['code'] == 1){
                        $updateRes = $this->save($decide['update']);
                        if($updateRes > 0){
                            $insertData['id'] = $decide['update']['id'];
                            $oldCampaigns[$insertData['media_campaignid']] = $insertData;
                        } else {
                            //TODO 更新失败 记录日志
                            break;
                        }
                    }
                }
            }
            
        } while(false);
        
        return array_values($oldCampaigns);
    }
    
    /**
     * 获取 bing 推广计划基本信息
     * @param array $accInfo 数据库读取的广告帐户信息
     * @param array $campaigns 以 account_id 为key的推广计划信息数组
     *
     * 尽量避免使用 ON DUPLICATE KEY UPDATE 高并发下会有性能问题
     */
    public function getCampaignBaseBing($accInfo = [], $campaigns = []){
        $campaign = isset($campaigns[$accInfo['id']]) ? $campaigns[$accInfo['id']] : [];
        $oldCampaigns = array_column($campaign, null, 'media_campaignid');
        do{
            $advCampaignBing = new AdvCampaignBing();
            $advCampaigns = $advCampaignBing->getAllCampaigns($accInfo['devkey'], $accInfo['accessToken'], $accInfo['media_account_id']);
            if(isset($advCampaigns['OperationErrors']) || isset($advCampaigns['Errors'])){
                break;
            }
            $newCampaigns = $advCampaigns['Campaigns'];
            //dump($newCampaigns);
            //$newCampaigns = array_column($data, 'campaignId');
            if(count($newCampaigns) > 0){
                foreach ($newCampaigns as $v){
                    //获取广告计划设置
                    $settings = $advCampaignBing->getCampaignSettings($accInfo['devkey'], $accInfo['accessToken'], $accInfo['media_account_id'], $v['Id']);
                    //dump($settings);
                    $insertData = [
                        'account_id'=>$accInfo['id'],
                        'media_campaignid'=>$v['Id'],
                        'campaign_name'=>$v['Name'],
                        'budget'=>$v['DailyBudget']*100,        //单位 分
                        'region'=>'',
                        'schedule'=>'',
                        'status'=>$this->_bingCampaignStatusMap($v['Status'])['statusCode'],
                        'media_status'=>$this->_bingCampaignStatusMap($v['Status'])['statusTxt'],
                        'addTime'=>'',
                        'device'=>'3',                          //TODO 使用 GetCampaignCriterionsByIds 获取投放设备信息
                        'negativeWords'=>'',
                        'exactNegativeWords'=>'',
                        'campaignBidType'=>$this->_bingGetCampaignBidId($v['BiddingScheme'], 'campaignBidType'),
                        'campaignBid'=>$this->_bingGetCampaignBidId($v['BiddingScheme'], 'campaignBid')
                    ];
                    //过滤掉为 NULL的键值
                    foreach ($insertData as $k=>$v){
                        if($v == null){
                            unset($insertData[$k]);
                        }
                    }
                    //dump($insertData);
                    $decide = $this->insertOrUpdateOrNothing($insertData, $oldCampaigns);
                    if($decide['code'] == 2){
                        $insertID = $this->insert($insertData, true);
     
                        if($insertID){
                            $insertData['id'] = $insertID;
                            $oldCampaigns[$insertData['media_campaignid']] = $insertData;
                        } else {
                            //TODO 写入失败 记录日志
                            break;
                        }
                    } elseif($decide['code'] == 1){
                        $updateRes = $this->save($decide['update']);
                        if($updateRes > 0){
                            $insertData['id'] = $decide['update']['id'];
                            $oldCampaigns[$insertData['media_campaignid']] = $insertData;
                        } else {
                            //TODO 更新失败 记录日志
                            break;
                        }
                    }
                }
            }
            
        } while(false);
        
        return array_values($oldCampaigns);
    }
    
    /**
     * 判断是否插入新数据还是更新数据
     * 如果数据未发生更改 则什么都不做
     * @param unknown $insertData
     * @param unknown $oldCampaigns
     * 
     * return code == 2 插入
     *        code == 1 更新
     *        code == 0 什么都不做
     */
    private function insertOrUpdateOrNothing($insertData, $oldCampaigns){
        if(!isset($oldCampaigns[$insertData['media_campaignid']])){
            return ['code'=>2,'update'=>[]];
        }
        
        $update = [];
        $compare = $oldCampaigns[$insertData['media_campaignid']];
        foreach ($insertData as $k=>$v){
            //慎用  !== 比较   数据库类型为int 但PDO驱动查询会全部转为string类型
            //此外数据库字段类型为json的话 会自动伸缩json结构 使得无法使用 != 操作符来进行字符比对 解决方案 数据表采用string字段类型
            if(in_array($k, $this->needUpdateFields) && ($v != $compare[$k])){      
                $update[$k] = $v;
            }
        }
        //dump($update);
        //dump($compare);
        if(count($update) > 0){
            $update['id'] = $compare['id'];
            return ['code'=>1,'update'=>$update];
        } else {
            return ['code'=>0,'update'=>[]];
        }
    }

    // 带事务的批量新增
    /* public function batchAddCampaigns(array $campaigns)
    {
        $this->db->transStart();
        
        $insertedIds = [];
        foreach ($campaigns as $campaign) {
            $id = $this->addCampaign($campaign);
            if ($id) {
                $insertedIds[] = $id;
            } else {
                $this->db->transRollback();
                return false;
            }
        }
        
        $this->db->transComplete();
        return $insertedIds;
    } */
    
    /**
     * 转换 360 广告计划的status字段
     * 详细文档可见 https://open.e.360.cn/api/campaign_getInfoByIdList.html status字段说明
     * @param unknown $status
     * @return number
     */
    private function _360CampaignStatusMap($status){
        switch ($status){
            case 'enable':
                return ['statusCode'=>1,'statusTxt'=>'正常投放'];
            case 'pause':
                return ['statusCode'=>0,'statusTxt'=>'暂停投放'];
        }
        return ['statusCode'=>1,'statusTxt'=>'正常投放'];
    }
    
    /**
     * 转换 百度 广告计划的status字段
     * 详细文档可见 https://dev2.baidu.com/content?sceneType=0&pageId=100259&nodeId=360  status字段说明
     * @param unknown $status
     * 21 - 有效
     * 22 - 处于暂停时段
     * 23 - 暂停推广
     * 24 - 计划预算不足
     * 25 - 账户预算不足
     * @return number
     */
    private function _baiduCampaignMap($data, $type = 'status'){
        if($type == 'status'){
            switch ($data){
                case 21:
                    return ['statusCode'=>1,'statusTxt'=>'正常投放'];
                case 22:
                    return ['statusCode'=>0,'statusTxt'=>'时段暂停'];
                case 23:
                    return ['statusCode'=>0,'statusTxt'=>'暂停推广'];
                case 24:
                    return ['statusCode'=>0,'statusTxt'=>'计划预算不足'];
                case 25:
                    return ['statusCode'=>0,'statusTxt'=>'账户预算不足'];
            }
            return ['statusCode'=>1,'statusTxt'=>'正常投放'];
        } else if($type == 'device'){
            return $data == 1 ? 2 : ($data == 2 ? 1 : $data);
        }
        return $data;
    }
    
    /**
     * 转换 Bing 广告计划的status字段
     * 详细文档可见 https://learn.microsoft.com/zh-cn/advertising/campaign-management-service/campaignstatus?view=bingads-13
     * Active、BudgetAndManualPaused、BudgetPaused、Deleted、Paused、Suspended
     * @param unknown $status
     * @return number
     */
    private function _bingCampaignStatusMap($status){
        switch ($status){
            case 'Active':
                return ['statusCode'=>1,'statusTxt'=>'正常投放'];
            case 'BudgetAndManualPaused':
                return ['statusCode'=>0,'statusTxt'=>'预算不足'];
            case 'BudgetPaused':
                return ['statusCode'=>0,'statusTxt'=>'预算不足'];
            case 'Deleted':
                return ['statusCode'=>0,'statusTxt'=>'已删除'];
            case 'Paused':
                return ['statusCode'=>0,'statusTxt'=>'暂停投放'];
            case 'Suspended':
                return ['statusCode'=>0,'statusTxt'=>'审查中'];
        }
        return ['statusCode'=>1,'statusTxt'=>'正常投放'];
    }
    
    /**
     * 获取广告计划相关联的ocpc信息
     * @param unknown $campInfo 广告计划信息
     * @param unknown $ocpcInfo ocpc信息
     */
    private function _360GetCampaignOcpcInfo($acc){
        $ocpc = new Ocpc();
        $ocpcIds = $ocpc->ocpcList($acc['devkey'], $acc['accessToken']);
        $ocpcInfo = $ocpc->ocpcCampaignIds($acc['devkey'], $acc['accessToken'], $ocpcIds['data']);
        //使用ocpc的广告计划
        $ocpcCampaignsMap = [];
        if(($ocpcInfo['errno'] == 0) && ($ocpcInfo['total'] > 0)){
            foreach ($ocpcInfo['data'] as $ocpcId=>$v){
                if($v['device'] == '0'){
                    foreach ($v['plan_bind'] as $campId=>$_ocpc){
                        $ocpcCampaignsMap[$campId]['pc'] = $v;
                    }
                } elseif($v['device'] == '1') {
                    foreach ($v['plan_bind'] as $campId=>$_ocpc){
                        $ocpcCampaignsMap[$campId]['mobile'] = $v;
                    }
                }
            }
        }
        //dump($ocpcCampaignsMap);
        //exit;
        return $ocpcCampaignsMap;
    }
    
    /**
     * 从 ocpc信息中提取出价信息（PC、移动端）
     * @param unknown $campInfo 广告计划信息
     * @param unknown $ocpcInfo ocpc信息
     */
    private function _360GetCampaignBidInfo($campInfo, $ocpcInfo){
        $bidInfo = [];
        if($campInfo['device'] == '0'){
            //pc和移动
            if(isset($ocpcInfo[$campInfo['id']]['pc'])){
                //$bidInfo['campaignBid'] = -1;
                $bidInfo['campaignBidType'] = 1;                                                                            //cpc出价模式
                $bidInfo['campaignOcpcBid'] = $ocpcInfo[$campInfo['id']]['pc']['exp_amt'] * 100;                            //ocpc出价    单位 分
                $bidInfo['campaignOcpcBidType'] = $ocpcInfo[$campInfo['id']]['pc']['ocpc_stage_one_expand_type'];           //ocpc出价模式
                $bidInfo['ad_ocpc_id'] = $ocpcInfo[$campInfo['id']]['pc']['id'];                                            //与广告计划关联的pc端ocpc包id
            }
            if(isset($ocpcInfo[$campInfo['id']]['mobile'])){
                //$bidInfo['mCampaignBid'] = -1;
                $bidInfo['mCampaignBidType'] = 1;
                $bidInfo['mCampaignOcpcBid'] = $ocpcInfo[$campInfo['id']]['mobile']['exp_amt'] * 100;
                $bidInfo['mCampaignOcpcBidType'] = $ocpcInfo[$campInfo['id']]['mobile']['ocpc_stage_one_expand_type'];
                $bidInfo['m_ad_ocpc_id'] = $ocpcInfo[$campInfo['id']]['mobile']['id'];                                      //与广告计划关联的移动端ocpc包id
            }
        } else {
            //仅移动
            if(isset($ocpcInfo[$campInfo['id']]['mobile'])){
                //$bidInfo['mCampaignBid'] = -1;
                $bidInfo['mCampaignBidType'] = 1;
                $bidInfo['mCampaignOcpcBid'] = $ocpcInfo[$campInfo['id']]['mobile']['exp_amt'] * 100;
                $bidInfo['mCampaignOcpcBidType'] = $ocpcInfo[$campInfo['id']]['mobile']['ocpc_stage_one_expand_type'];
                $bidInfo['m_ad_ocpc_id'] = $ocpcInfo[$campInfo['id']]['mobile']['id'];
            }
        }
        return $bidInfo;
    }
    
    /**
     * Bing获取广告计划出价策略id（系统内部id）
     * @param unknown $campaignBidType Bing的BiddingScheme出价策略
     */
    private function _bingGetCampaignBidId($biddingScheme, $type){
        if($type == 'campaignBidType'){
            if(isset($biddingScheme['Type'])){
                switch ($biddingScheme['Type']){
                    case 'MaxConversions':
                        return 5;
                    case 'EnhancedCpc':
                        return 8;
                        //TODO 增添更多出价策略类型
                    default:
                        return 0;
                }
            }
            return null;
        } else if($type == 'campaignBid'){
            if(isset($biddingScheme['MaxCpc']['Amount'])){
                return floatval($biddingScheme['MaxCpc']['Amount']) * 100;
            }
            return null;
        }
    }
    
    
    /**
     * 更新 360 广告计划
     * @param array $accInfo
     * @param array $body
     * @return void|mixed
     */
    public function updateCampaign360($accInfo = [], $body = []){
        if(count($body) == 0){
            return;
        }
        //dump($body);
        if(isset($body['campaignOcpcBid']) || isset($body['mCampaignOcpcBid'])){
            $ocpc_id = isset($body['ad_ocpc_id']) ? $body['ad_ocpc_id'] : $body['m_ad_ocpc_id'];
            $ocpc_price = isset($body['ad_ocpc_id']) ? $body['campaignOcpcBid'] : $body['mCampaignOcpcBid'];
            $ocpc = new Ocpc();
            $ocpcs = [
                [
                    'id'=>$ocpc_id,
                    'price'=>$ocpc_price
                ]
            ];
            $res = $ocpc->ocpcUpdate($accInfo['devkey'], $accInfo['accessToken'], $ocpcs);
            //dump($result);
            if(count($res['data']['failures']) == 0){
                //修改成功 更新数据库
                $body = $this->_updateCampaignDecoDb($body);
                $this->update($body['campaign_id'], $body);
            }
        } else {
            $advCampaign360  = new AdvCampaign360();
            //数组的key是媒体方名称 value为内部名称
            $_fieldsMap = [
                'id'=>'media_campaignid',           
                'budget'=>'budget',
                'status'=>'status'
            ];
            $_mapBody = [];
            foreach ($_fieldsMap as $k=>$v){
                if(isset($body[$v])){
                    $_mapBody[$k] = $this->_updateCampaign360Deco($k, $body[$v]);
                }
            }
            $res = $advCampaign360->updateCampaign($accInfo['devkey'], $accInfo['accessToken'], $_mapBody);
            if(!isset($res['failures'])){
                //修改成功 更新数据库
                $body = $this->_updateCampaignDecoDb($body);
                $this->update($body['campaign_id'], $body);
            }
        }
        return $res;
    }
    
    /**
     * 请求 360 marketing api的数据修饰
     * @param unknown $k
     * @param unknown $v
     * @return boolean|unknown
     */
    public function _updateCampaign360Deco($k, $v){
        if($k == 'status'){
            return $v == '1' ? 'pause' : 'enable';        //将字符串转为布尔值 然后再取反
        }
        return $v;
    }
    
    /**
     * 更新  Baidu 广告计划
     * @param array $accInfo
     * @param array $body
     * @return void|mixed
     */
    public function updateCampaignBaidu($accInfo = [], $body = []){
        if(count($body) == 0){
            return;
        }
        $_fieldsMap = [
            'campaignId'=>'media_campaignid',
            'budget'=>'budget',
            'pause'=>'status',
            'campaignBid'=>'campaignBid',
            'campaignOcpcBid'=>'campaignOcpcBid'
        ];
        $_mapBody = [];
        foreach ($_fieldsMap as $k=>$v){
            if(isset($body[$v])){
                $_mapBody[$k] = $this->_updateCampaignBaiduDeco($k, $body[$v]);
            }
        }
        $_mapBodys = ['campaignTypes'=>[$_mapBody]];
        $advCampaignBaidu  = new AdvCampaignBaidu();
        $res = $advCampaignBaidu->updateCampaign($accInfo['username'], $accInfo['accessToken'], $_mapBodys);
        //dump($_mapBodys);
        //dump($res);
        //exit;
        if(count($res['header']['failures']) == 0){
            //修改成功 更新数据库
            $body = $this->_updateCampaignDecoDb($body);
            $this->update($body['campaign_id'], $body);
        }
        return $res;
    }
    
    /**
     * 请求百度marketing api的数据修饰
     * @param unknown $k
     * @param unknown $v
     * @return boolean|unknown
     */
    public function _updateCampaignBaiduDeco($k, $v){
        if($k == 'pause'){
            return filter_var($v, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);        //将字符串转为布尔值 然后再取反
        }
        return $v;
    }
    
    /**
     * 更新  Bing 广告计划
     * @param array $accInfo
     * @param array $body
     * @return void|mixed
     */
    public function updateCampaignBing($accInfo = [], $body = []){
        if(count($body) == 0){
            return;
        }
        $updateData = $this->_updateCampaignBingDeco($body);
        $advCampaignBing  = new AdvCampaignBing();
        $res = $advCampaignBing->updateCampaigns($accInfo['devkey'], $accInfo['accessToken'], $accInfo['media_account_id'], $updateData);
        //dump($_mapBodys);
        //dump($res);
        //exit;
        if(isset($res['PartialErrors']) && count($res['PartialErrors']) == 0){
            //修改成功 更新数据库
            $body = $this->_updateCampaignDecoDb($body);
            $updateRes = $this->update($body['campaign_id'], $body);
            //dump($body);
            //dump($updateRes);
        }
        return $res;
    }
    
    /**
     * 请求 Bing marketing api的数据修饰
     * @param array $arr
     * @return boolean|unknown
     */
    public function _updateCampaignBingDeco($arr){
        $_body = ['Id'=>$arr['media_campaignid']];
        if (isset($arr['status'])){
            if(intval($arr['status']) > 0){
                $_body['Status'] = 'Paused';
            } else {
                $_body['Status'] = 'Active';
            }
        }
        if(isset($arr['budget'])){
            $_body['DailyBudget'] = floatval($arr['budget']);
        }
        return $_body;
    }
    
    /**
     * 数据入库前的处理
     * @param unknown $k
     * @param unknown $v
     * @return boolean|unknown
     */
    public function _updateCampaignDecoDb($data){
        if(isset($data['budget'])){
            $data['budget'] = $data['budget'] * 100;
        }
        if(isset($data['campaignOcpcBid'])){
            $data['campaignOcpcBid'] = $data['campaignOcpcBid'] * 100;
        }
        if(isset($data['mCampaignOcpcBid'])){
            $data['mCampaignOcpcBid'] = $data['mCampaignOcpcBid'] * 100;
        }
        if(isset($data['campaignBid'])){
            $data['campaignBid'] = $data['campaignBid'] * 100;
        }
        if(isset($data['mCampaignBid'])){
            $data['mCampaignBid'] = $data['mCampaignBid'] * 100;
        }
        if(isset($data['status'])){
            $data['status'] = (string)(1 - intval($data['status']));
        }
        return $data;
    }
    
}