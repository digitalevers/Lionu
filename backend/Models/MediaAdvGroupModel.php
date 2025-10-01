<?php
/**
 * 广告组数据模型
 */
namespace App\Models;

use App\Libraries\MarketingApi360\SEM\AdvManage\AdvGroup as AdvGroup360;
use App\Libraries\MarketingApiBaidu\SEM\AdvManage\AdvGroup as AdvGroupBaidu;
use App\Libraries\MarketingApiBing\SEM\AdvManage\AdvGroup as AdvGroupBing;

class MediaAdvGroupModel extends \CodeIgniter\Model
{
    protected $table = 'dev_media_advgroup';
    
    //关闭字段保护 使得不填写 $allowedFields 也能插入数据
    protected $protectFields = false;
    
    //允许更新的字段
    protected $allowedFields = [
        'advgroup_name',
        'updateTime',
        'status',
        'price',
        'negativeWords',
        'exactNegativeWords'
    ];
    
    //需要更新的字段
    protected $needUpdateFields = [
        'advgroup_name',
        'updateTime',
        'status',
        'price',
        'negativeWords',
        'exactNegativeWords'
    ];
    
    /**
     * 查找所有广告组并以 account_id作为键名组装数组返回
     * @param string $where
     * @return array
     */
    public function getAdvGroups($accounts, $groupKey = 'account_id'){
        $res = [];
        $accountIds = array_keys($accounts);
        if(count($accountIds) > 0){
            $_aids = implode(',', $accountIds);
            $allAdvGroups = $this->where('account_id IN('.$_aids.')')->findAll();
            //dump($allCampaigns);
            //exit;
            if(count($allAdvGroups) > 0){
                $res = groupByKey($allAdvGroups, $groupKey);
            }
        }
        return $res;
    }
    
    /**
     * 360
     * 获取广告组信息
     * @param unknown $accInfo
     */
    public function getAdvGroupBase360($accInfo, $oldAdvGroups = []){
        $oldAdvGroups = isset($oldAdvGroups[$accInfo['id']]) ? $oldAdvGroups[$accInfo['id']] : [];
        $oldAdvGroupsMap = array_column($oldAdvGroups, null, 'media_advgroupid');
        do {
            if(count($accInfo['campaigns']) > 0){
                $campaignMap = array_column($accInfo['campaigns'], null, 'media_campaignid');
                $advGroupIDs = [];
                $advGroup360 = new AdvGroup360();
                //dump($accInfo);
                foreach ($accInfo['campaigns'] as $campaign){
                    $advGroups = $advGroup360->getAllAdvGroups($accInfo['devkey'], $accInfo['accessToken'], $campaign['media_campaignid']);
                    if(isset($advGroups['failures']) && count($advGroups['failures']) > 0){
                        break;
                    }
                    $advGroupIDs = array_merge($advGroupIDs, $advGroups['groupIdList']);
                }
                //获取广告组详情
                if(count($advGroupIDs) > 0){
                    $advGroupDetails = $advGroup360->getAdvGroupDetail($accInfo['devkey'], $accInfo['accessToken'], $advGroupIDs);
                    if(isset($advGroupDetails['failures']) && count($advGroupDetails['failures']) > 0){
                        break;
                    }
                    //dump($advGroupDetails);
                    if(isset($advGroupDetails['groupList']) && (count($advGroupDetails['groupList']) > 0)){
                        foreach ($advGroupDetails['groupList'] as $advGroup){
                            $insertGroup = [
                                'channel_id'=>$accInfo['channel_id'],
                                'account_id'=>$accInfo['id'],
                                'campaign_id'=>$campaignMap[$advGroup['campaignId']]['id'],
                                'media_advgroupid'=>$advGroup['id'],
                                'media_campaignid'=>$advGroup['campaignId'],
                                'campaign_name'=>$campaignMap[$advGroup['campaignId']]['campaign_name'],
                                'advgroup_name'=>$advGroup['name'],
                                'updateTime'=>$advGroup['updateTime'],
                                'addTime'=>$advGroup['addTime'],
                                'status'=>$this->_360AdvGroupStatusMap($advGroup['status'])['statusCode'],
                                'media_status'=>$advGroup['status'],
                                'price'=>intval($advGroup['price'] * 100),
                                'negativeWords'=>$advGroup['negativeWords'],
                                'exactNegativeWords'=>$advGroup['exactNegativeWords'],
                            ];
                            $decide = $this->insertOrUpdateOrNothing($insertGroup, $oldAdvGroupsMap);
                            if($decide['code'] == 2){
                                $insertID = $this->insert($insertGroup, true);
                                if($insertID){
                                    $insertGroup['id'] = $insertID;
                                    $oldAdvGroupsMap[$insertGroup['media_advgroupid']] = $insertGroup;
                                } else {
                                    //TODO 写入失败 记录日志
                                    break;
                                }
                            } elseif($decide['code'] == 1){
                                $updateRes = $this->save($decide['update']);
                                if($updateRes > 0){
                                    $insertGroup['id'] = $decide['update']['id'];
                                    $oldAdvGroupsMap[$insertGroup['media_advgroupid']] = $insertGroup;
                                } else {
                                    //TODO 更新失败 记录日志
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        } while(false);
        
        return array_values($oldAdvGroupsMap);
    }
    
    /**
     * 获取百度 "方案" 模块信息
     * @param unknown $accInfo
     * @param unknown $oldAdvGroups
     */
    public function getAdvGroupBaseBaidu($accInfo, $oldAdvGroups = []){
        $oldAdvGroups = isset($oldAdvGroups[$accInfo['id']]) ? $oldAdvGroups[$accInfo['id']] : [];
        $oldAdvGroupsMap = array_column($oldAdvGroups, null, 'media_advgroupid');
        do {
            if(count($accInfo['campaigns']) > 0){
                $campaignMap = array_column($accInfo['campaigns'], null, 'media_campaignid');
                $campaignIDs = array_column($accInfo['campaigns'], 'media_campaignid');
                //获取百度投放"方案" 详情信息
                $advGroupBaidu = new AdvGroupBaidu();
                $baiduGroups = $advGroupBaidu->getAllAdvGroups($accInfo['username'], $accInfo['accessToken'], 3, $campaignIDs);
                if(count($baiduGroups['header']['failures']) > 0){
                    break;
                }
                $advGroups = $baiduGroups['body']['data'];
                if(count($advGroups) > 0){
                    foreach ($advGroups as $advGroup){
                        $insertGroup = [
                            'channel_id'=>$accInfo['channel_id'],
                            'account_id'=>$accInfo['id'],
                            'campaign_id'=>$campaignMap[$advGroup['campaignId']]['id'],
                            'media_advgroupid'=>$advGroup['adgroupId'],
                            'media_campaignid'=>$advGroup['campaignId'],
                            'campaign_name'=>$campaignMap[$advGroup['campaignId']]['campaign_name'],
                            'advgroup_name'=>$advGroup['adgroupName'],
                            'addTime'=>$advGroup['createTime'],
                            'status'=>$this->_baiduCampaignStatusMap($advGroup['status'])['statusCode'],
                            'media_status'=>$advGroup['status'],
                            'price'=>intval($advGroup['maxPrice'] * 100),
                            'negativeWords'=>json_encode($advGroup['negativeWords']),
                            'exactNegativeWords'=>json_encode($advGroup['exactNegativeWords']),
                        ];
                        
                        $decide = $this->insertOrUpdateOrNothing($insertGroup, $oldAdvGroupsMap);
                        if($decide['code'] == 2){
                            $insertID = $this->insert($insertGroup, true);
                            if($insertID){
                                $insertGroup['id'] = $insertID;
                                $oldAdvGroupsMap[$insertGroup['media_advgroupid']] = $insertGroup;
                            } else {
                                //TODO 写入失败 记录日志
                                break;
                            }
                        } elseif($decide['code'] == 1){
                            $updateRes = $this->save($decide['update']);
                            if($updateRes > 0){
                                $insertGroup['id'] = $decide['update']['id'];
                                $oldAdvGroupsMap[$insertGroup['media_advgroupid']] = $insertGroup;
                            } else {
                                //TODO 更新失败 记录日志
                                break;
                            }
                        }
                    }
                }
            }
        } while(false);
        
        return $oldAdvGroupsMap;
    }
    
    /**
     * 获取Bing广告组信息
     * @param unknown $accInfo
     * @param unknown $oldAdvGroups
     */
    public function getAdvGroupBaseBing($accInfo, $oldAdvGroups = []){
        $oldAdvGroups = isset($oldAdvGroups[$accInfo['id']]) ? $oldAdvGroups[$accInfo['id']] : [];
        $oldAdvGroupsMap = array_column($oldAdvGroups, null, 'media_advgroupid');
        do {
            if(count($accInfo['campaigns']) > 0){
                $campaignMap = array_column($accInfo['campaigns'], null, 'media_campaignid');
                //获取所有广告活动ids
                $campaignIds = array_column($accInfo['campaigns'], 'media_campaignid');
                $advGroupBing = new AdvGroupBing();
                foreach ($campaignIds as $campaignId){
                    $bingGroups = $advGroupBing->getAllAdvGroups($accInfo['devkey'], $accInfo['accessToken'], $accInfo['media_account_id'], $campaignId);
                    if(isset($advCampaigns['OperationErrors']) || isset($advCampaigns['Errors'])){
                        break;
                    }
                    $advGroups = $bingGroups['AdGroups'];
                    if(count($advGroups) > 0){
                        foreach ($advGroups as $advGroup){
                            $insertGroup = [
                                'channel_id'=>$accInfo['channel_id'],
                                'account_id'=>$accInfo['id'],
                                'campaign_id'=>$campaignMap[$campaignId]['id'],
                                'media_advgroupid'=>$advGroup['Id'],
                                'media_campaignid'=>$campaignId,
                                'campaign_name'=>$campaignMap[$campaignId]['campaign_name'],
                                'advgroup_name'=>$advGroup['Name'],
                                'addTime'=>'',
                                'status'=>$this->_bingCampaignStatusMap($advGroup['Status'])['statusCode'],
                                'media_status'=>$advGroup['Status'],
                                'price'=>intval($advGroup['CpcBid']['Amount'] * 100),
                                'negativeWords'=>'',
                                'exactNegativeWords'=>'',
                            ];
                            
                            $decide = $this->insertOrUpdateOrNothing($insertGroup, $oldAdvGroupsMap);
                            if($decide['code'] == 2){
                                $insertID = $this->insert($insertGroup, true);
                                if($insertID){
                                    $insertGroup['id'] = $insertID;
                                    $oldAdvGroupsMap[$insertGroup['media_advgroupid']] = $insertGroup;
                                } else {
                                    //TODO 写入失败 记录日志
                                    break;
                                }
                            } elseif($decide['code'] == 1){
                                $updateRes = $this->save($decide['update']);
                                if($updateRes > 0){
                                    $insertGroup['id'] = $decide['update']['id'];
                                    $oldAdvGroupsMap[$insertGroup['media_advgroupid']] = $insertGroup;
                                } else {
                                    //TODO 更新失败 记录日志
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        } while(false);
        
        return $oldAdvGroupsMap;
    }
    
    /**
     * 判断是否插入新数据还是更新数据
     * 如果数据未发生更改 则什么都不做
     * @param unknown $insertGroup
     * @param unknown $oldAdvGroups
     *
     * return code == 2 插入
     *        code == 1 更新
     *        code == 0 什么都不做
     */
    private function insertOrUpdateOrNothing($insertGroup, $oldAdvGroupsMap){
        if(!isset($oldAdvGroupsMap[$insertGroup['media_advgroupid']])){
            return ['code'=>2,'update'=>[]];
        }
        
        $update = [];
        $compare = $oldAdvGroupsMap[$insertGroup['media_advgroupid']];
        foreach ($insertGroup as $k=>$v){
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
    
    /**
     * 修改 360 广告组
     */
    public function updateAdvGroup360($accInfo = [], $body = []){
        if(count($body) == 0){
            return;
        }
        $advGroup360  = new AdvGroup360();
        $_fieldsMap = [
            'id'=>'media_advgroupid',           
            'price'=>'price',                   //广告组出价
            'status'=>'status'                  //广告组状态
        ];
        $_mapBody = [];
        foreach ($_fieldsMap as $k=>$v){
            if(isset($body[$v])){
                $_mapBody[$k] = $this->_updateAdvGroup360Deco($k, $body[$v]);
            }
        }
        $res = $advGroup360->updateAdvGroup($accInfo['devkey'], $accInfo['accessToken'], $_mapBody);
        //dump($res);
        if(!isset($res['failures'])){
            //修改成功 更新数据库
            $body = $this->_updateAdvGroupDecoDb($body, '360');
            $mainkey_value = $body['advgroup_id'];
            unset($body['advgroup_id']);                //由于关闭了字段保护 所有多出的字段无法写入 需要unset之后才能写入
            //dump($body);
            $updateRes = $this->update($mainkey_value, $body);
            //echo $this->getLastQuery();
            //dump($updateRes);
        }
        return $res;
    }
    
    /**
     * 请求 360 marketing api的数据修饰
     * @param unknown $k
     * @param unknown $v
     * @return boolean|unknown
     */
    public function _updateAdvGroup360Deco($k, $v){
        if($k == 'status'){
            return $v == '1' ? 'pause' : 'enable';     
        }
        return $v;
    }
    
    /**
     * 更新  Baidu 广告组
     * @param array $accInfo
     * @param array $body
     * @return void|mixed
     */
    public function updateAdvGroupBaidu($accInfo = [], $body = []){
        if(count($body) == 0){
            return;
        }
        $_fieldsMap = [
            'adgroupId'=>'media_advgroupid',
            'maxPrice'=>'price',                //广告组出价 
            'pause'=>'status'                   //广告组状态
        ];
        $_mapBody = [];
        foreach ($_fieldsMap as $k=>$v){
            if(isset($body[$v])){
                $_mapBody[$k] = $this->_updateAdvGroupBaiduDeco($k, $body[$v]);
            }
        }
        $_mapBodys = ['adgroupTypes'=>[$_mapBody]];
        $advGroupBaidu  = new AdvGroupBaidu();
        $res = $advGroupBaidu->updateAdvGroup($accInfo['username'], $accInfo['accessToken'], $_mapBodys);
        //dump($body);
        //dump($_mapBodys);
        //dump($res);
        //exit;
        if(count($res['header']['failures']) == 0){
            //修改成功 更新数据库
            $body = $this->_updateAdvGroupDecoDb($body, 'baidu');
            $mainkey_value = $body['advgroup_id'];
            unset($body['advgroup_id']);
            $updateRes = $this->update($mainkey_value, $body);
            //dump($updateRes);
        }
        return $res;
    }
    
    /**
     * 请求百度marketing api的数据修饰
     * @param unknown $k
     * @param unknown $v
     * @return boolean|unknown
     */
    public function _updateAdvGroupBaiduDeco($k, $v){
        if($k == 'pause'){
            return filter_var($v, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);        //将字符串转为布尔值 然后再取反
        }
        return $v;
    }
    
    /**
     * 更新  Bing 广告组
     * @param array $accInfo
     * @param array $body
     * @return void|mixed
     */
    public function updateAdvGroupBing($accInfo = [], $body = []){
        if(count($body) == 0){
            return;
        }
        $_body = $this->_updateAdvGroupBingDeco($body);
        $advGroupBing  = new AdvGroupBing();
        $res = $advGroupBing->updateAdvGroup($accInfo['devkey'], $accInfo['accessToken'], $accInfo['media_account_id'], $_body);
        //dump($body);
        //dump($_mapBodys);
        //dump($res);
        //exit;
        if(isset($res['PartialErrors']) && count($res['PartialErrors']) == 0){
            //修改成功 更新数据库
            $body = $this->_updateAdvGroupDecoDb($body, 'bing');
            $mainkey_value = $body['advgroup_id'];
            unset($body['advgroup_id']);
            $updateRes = $this->update($mainkey_value, $body);
            //dump($updateRes);
        }
        return $res;
    }
    
    /**
     * 请求 Bing marketing api的数据修饰
     * @param array $arr
     * @return boolean|unknown
     */
    public function _updateAdvGroupBingDeco($arr){
        $_body = [
            "CampaignId"=>$arr['media_campaignid'],
            "AdGroups"=>[
                ['Id'=>$arr['media_advgroupid']]
            ]
        ];
        if(isset($arr['status'])){
            if(intval($arr['status']) > 0){
                $_body['AdGroups'][0]['Status'] = 'Paused';
            } else {
                $_body['AdGroups'][0]['Status'] = 'Active';
            }
        }
        if(isset($arr['price'])){
            $_body['AdGroups'][0]['CpcBid']['Amount'] = floatval($arr['price']);
        }
        return $_body;
    }
    
    /**
     * 数据入库时的修饰
     * @param unknown $k
     * @param unknown $v
     * @return boolean|unknown
     */
    public function _updateAdvGroupDecoDb($data, $media){
        if(isset($data['price'])){
            $data['price'] = $data['price'] * 100;
        }
        if(isset($data['status'])){
            $data['status'] = (string)(1 - intval($data['status']));
            switch($media){
                case '360':
                    $data['media_status'] = intval($data['status']) == 1 ? 'enable' : 'pause';
                    break;
                case 'baidu':
                    $data['media_status'] = intval($data['status']) == 1 ? 31 : 32;
                    break;
                case 'bing':
                    $data['media_status'] = intval($data['status']) == 1 ? 'Active' : 'Paused';
                    break;
                default:
                    break;
            }
        }
        return $data;
    }
    
    /**
     * 转换 360 广告组的status字段
     * 详细文档可见 https://open.e.360.cn/api/group_getInfoByIdList.html status字段说明
     * @param unknown $status
     * @return number
     */
    private function _360AdvGroupStatusMap($status){
        switch ($status){
            case 'enable':
                return ['statusCode'=>1,'statusTxt'=>'正常投放'];
            case 'pause':
                return ['statusCode'=>0,'statusTxt'=>'暂停投放'];
        }
        return ['statusCode'=>1,'statusTxt'=>'正常投放'];
    }
    
    /**
     * 转换 百度 广告组的status字段
     * 详细文档可见 https://dev2.baidu.com/content?sceneType=0&pageId=100264&nodeId=351&subhead=  status字段说明
     * @param unknown $status
     * 31 - 有效
     * 32 - 暂停推广
     * 33 - 推广计划暂停推广
     * 43 - 未审核
     * @return number
     */
    private function _baiduCampaignStatusMap($status){
        switch ($status){
            case 31:
                return ['statusCode'=>1,'statusTxt'=>'有效'];
            case 32:
                return ['statusCode'=>0,'statusTxt'=>'暂停推广'];
            case 33:
                return ['statusCode'=>0,'statusTxt'=>'推广计划暂停推广'];
            case 43:
                return ['statusCode'=>0,'statusTxt'=>'未审核'];
        }
        return ['statusCode'=>1,'statusTxt'=>'有效'];
    }
    
    /**
     * 转换 Bing 广告组的status字段
     * 详细文档可见  https://learn.microsoft.com/zh-cn/advertising/campaign-management-service/adgroupstatus?view=bingads-13
     * Active、Deleted、Expired、Paused
     * @param unknown $status
     * @return number
     */
    private function _bingCampaignStatusMap($status){
        switch ($status){
            case 'Active':
                return ['statusCode'=>1,'statusTxt'=>'正常投放'];
            case 'Deleted':
                return ['statusCode'=>0,'statusTxt'=>'已删除'];
            case 'Expired':
                return ['statusCode'=>0,'statusTxt'=>'已过期'];
            case 'Paused':
                return ['statusCode'=>0,'statusTxt'=>'暂停投放'];
        }
        return ['statusCode'=>1,'statusTxt'=>'正常投放'];
    }
}
