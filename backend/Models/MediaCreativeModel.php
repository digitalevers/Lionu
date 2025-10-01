<?php
/**
 * 创意数据模型
 */
namespace App\Models;

use App\Libraries\MarketingApi360\SEM\AdvManage\AdvCreative as AdvCreative360;
use App\Libraries\MarketingApiBaidu\SEM\AdvManage\AdvCreative as AdvCreativeBaidu;
use App\Libraries\MarketingApiBing\SEM\AdvManage\AdvCreative as AdvCreativeBing;

class MediaCreativeModel extends \CodeIgniter\Model
{
    protected $table = 'dev_media_creative';
    
    //关闭字段保护 使得不填写 $allowedFields 也能插入数据
    protected $protectFields = false;
    
    //允许更新的字段
    protected $allowedFields = [
        'status',
        'media_status'
     ];
    
    //需要更新的字段
    protected $needUpdateFields = [
        'title',
        'updateTime',
        'status',
        'description1',
        'campaign_name',
        'advgroup_name',
        'destinationUrl',
        'displayUrl',
        'mobileDestinationUrl',
        'mobileDisplayUrl',
        'media_creativetype'
    ];
    
    /**
     * 查找所有广告组并以 campaign_id作为键名组装数组返回
     * @param string $where
     * @return array
     */
    public function getCreatives($accounts, $groupKey = 'account_id'){
        $res = [];
        $accountIds = array_keys($accounts);
        if(count($accountIds) > 0){
            $_aids = implode(',', $accountIds);
            $allCreatives = $this->where('account_id IN('.$_aids.')')->findAll();
            //dump($allCampaigns);
            //exit;
            if(count($allCreatives) > 0){
                $res = groupByKey($allCreatives, $groupKey);
            }
        }
        return $res;
    }
    
    /**
     * 360
     * 获取创意信息
     * @param unknown $accInfo
     */
    public function getCreativeBase360($accInfo, $oldCreatives = []){
        $oldCreatives = isset($oldCreatives[$accInfo['id']]) ? $oldCreatives[$accInfo['id']] : [];
        $oldCreativesMap = array_column($oldCreatives, null, 'media_creativeid');
        do {
            if(count($accInfo['advgroups']) > 0){
                $advGroupMap = array_column($accInfo['advgroups'], null, 'media_advgroupid');
                $creativeIDs = [];
                $creative360 = new AdvCreative360();
                foreach ($accInfo['advgroups'] as $advgroup){
                    $creatives = $creative360->getAllCreativeIDs($accInfo['devkey'], $accInfo['accessToken'], $advgroup['media_advgroupid']);
                    if(isset($creatives['failures']) && count($creatives['failures']) > 0){
                        break;
                    }
                    $creativeIDs = array_merge($creativeIDs, $creatives['creativeIdList']);
                }
                //dump($advGroupMap);
                //exit;
                //获取创意详情
                if(count($creativeIDs) > 0){
                    $creativeDetails = $creative360->getCreativeDetail($accInfo['devkey'], $accInfo['accessToken'], $creativeIDs);
                    if(isset($creativeDetails['failures']) && count($creativeDetails['failures']) > 0){
                        break;
                    }
                    
                    if(isset($creativeDetails['creativeList']) && (count($creativeDetails['creativeList']) > 0)){
                        foreach ($creativeDetails['creativeList'] as $creative){
                            $insertCreative = [
                                'channel_id'=>$accInfo['channel_id'],
                                'account_id'=>$accInfo['id'],
                                'campaign_id'=>$advGroupMap[$creative['groupId']]['campaign_id'],
                                'media_campaignid'=>$creative['campaignId'],
                                'campaign_name'=>$advGroupMap[$creative['groupId']]['campaign_name'],
                                'advgroup_id'=>$advGroupMap[$creative['groupId']]['id'],
                                'media_advgroupid'=>$creative['groupId'],
                                'advgroup_name'=>$advGroupMap[$creative['groupId']]['advgroup_name'],
                                'media_creativeid'=>$creative['id'],
                                'title'=>$creative['title'],
                                'description1'=>$creative['description1'],
                                'destinationUrl'=>$creative['destinationUrl'],
                                'displayUrl'=>$creative['displayUrl'],
                                'mobileDestinationUrl'=>$creative['mobileDestinationUrl'],
                                'mobileDisplayUrl'=>$creative['mobileDisplayUrl'],
                                'updateTime'=>$creative['updateTime'],
                                'addTime'=>$creative['addTime'],
                                'status'=>$this->_360CreativeStatusMap($creative['optStatus'])['statusCode'],
                                'media_status'=>$creative['optStatus']
                            ];
                            
                            $decide = $this->insertOrUpdateOrNothing($insertCreative, $oldCreativesMap);
                            if($decide['code'] == 2){
                                $insertID = $this->insert($insertCreative, true);
                                if($insertID){
                                    $insertCreative['id'] = $insertID;
                                    $oldCreativesMap[$insertCreative['media_creativeid']] = $insertCreative;
                                } else {
                                    //TODO 写入失败 记录日志
                                    break;
                                }
                            } elseif($decide['code'] == 1){
                                $updateRes = $this->save($decide['update']);
                                if($updateRes > 0){
                                    $insertCreative['id'] = $decide['update']['id'];
                                    $oldCreativesMap[$insertCreative['media_creativeid']] = $insertCreative;
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
        
        return array_values($oldCreativesMap);
    }
    
    /**
     * 百度
     * 获取创意信息
     * @param array $accInfo
     * @param array $oldCreatives
     */
    public function getCreativeBaseBaidu($accInfo, $oldCreatives = []){
        $oldCreatives = isset($oldCreatives[$accInfo['id']]) ? $oldCreatives[$accInfo['id']] : [];
        $oldCreativesMap = array_column($oldCreatives, null, 'media_creativeid');
        do {
            if(count($accInfo['advgroups']) > 0){
                $creativeBaidu = new AdvCreativeBaidu();
                $advGroupIDs = array_column($accInfo['advgroups'], 'media_advgroupid');
                $advGroupMap = array_column($accInfo['advgroups'], null, 'media_advgroupid');
                $_creatives = $creativeBaidu->getAllCreatives($accInfo['username'], $accInfo['accessToken'], 5, $advGroupIDs);
                if(count($_creatives['header']['failures']) > 0){
                    break;
                }
                $creatives = $_creatives['body']['data'];
                //dump($creatives);
                //exit;
                if(count($creatives) > 0){
                    foreach ($creatives as $creative){
                        $insertCreative = [
                            'channel_id'=>$accInfo['channel_id'],
                            'account_id'=>$accInfo['id'],
                            'campaign_id'=>$advGroupMap[$creative['adgroupId']]['campaign_id'],
                            'media_campaignid'=>$advGroupMap[$creative['adgroupId']]['media_campaignid'],
                            'campaign_name'=>$advGroupMap[$creative['adgroupId']]['campaign_name'],
                            'advgroup_id'=>$advGroupMap[$creative['adgroupId']]['id'],
                            'media_advgroupid'=>$creative['adgroupId'],
                            'advgroup_name'=>$advGroupMap[$creative['adgroupId']]['advgroup_name'],
                            'media_creativeid'=>$creative['creativeId'],
                            'title'=>$creative['title'],
                            'description1'=>$creative['description1'],
                            'destinationUrl'=>isset($creative['pcDestinationUrl']) ? $creative['pcDestinationUrl'] : '',
                            'displayUrl'=>isset($creative['pcDisplayUrl']) ? $creative['pcDisplayUrl'] : '',
                            'mobileDestinationUrl'=>isset($creative['mobileDestinationUrl']) ? $creative['mobileDestinationUrl'] : '',
                            'mobileDisplayUrl'=>isset($creative['mobileDisplayUrl']) ? $creative['mobileDisplayUrl'] : '',
                            'addTime'=>$creative['createTime'],
                            'status'=>$this->_baiduCreativeStatusMap($creative['status'])['statusCode'],
                            'media_status'=>$creative['status']
                        ];
                        
                        $decide = $this->insertOrUpdateOrNothing($insertCreative, $oldCreativesMap);
                        if($decide['code'] == 2){
                            $insertID = $this->insert($insertCreative, true);
                            if($insertID){
                                $insertCreative['id'] = $insertID;
                                $oldCreativesMap[$insertCreative['media_creativeid']] = $insertCreative;
                            } else {
                                //TODO 写入失败 记录日志
                                break;
                            }
                        } elseif($decide['code'] == 1){
                            $updateRes = $this->save($decide['update']);
                            if($updateRes > 0){
                                $insertCreative['id'] = $decide['update']['id'];
                                $oldCreativesMap[$insertCreative['media_creativeid']] = $insertCreative;
                            } else {
                                //TODO 更新失败 记录日志
                                break;
                            }
                        }
                    }
                }
            }
        } while(false);
        
        return array_values($oldCreativesMap);
    }
    
    /**
     * 获取Bing广告信息
     * 
     * Bing 没有创意概念 即相当于 "广告" 层级
     * 
     * @param unknown $accInfo
     * @param unknown $oldAdvGroups
     */
    public function getCreativeBaseBing($accInfo, $oldCreatives = []){
        $oldCreatives = isset($oldCreatives[$accInfo['id']]) ? $oldCreatives[$accInfo['id']] : [];
        $oldCreativesMap = array_column($oldCreatives, null, 'media_creativeid');
        do {
            if(count($accInfo['advgroups']) > 0){
                $advGroupMap = array_column($accInfo['advgroups'], null, 'media_advgroupid');
                //获取所有广告组id
                $advGroupIDs = array_column($accInfo['advgroups'], 'media_advgroupid');
                $creativeBing = new AdvCreativeBing();
                foreach ($advGroupIDs as $advGroupId){
                    $bingCreatives = $creativeBing->getAllCreatives($accInfo['devkey'], $accInfo['accessToken'], $accInfo['media_account_id'], $advGroupId);
                    if(isset($bingCreatives['OperationErrors']) || isset($bingCreatives['Errors'])){
                        break;
                    }
                    $creatives = $bingCreatives['Ads'];
                    if(count($creatives) > 0){
                        foreach ($creatives as $creative){
                            $insertCreative = [
                                'channel_id'=>$accInfo['channel_id'],
                                'account_id'=>$accInfo['id'],
                                'campaign_id'=>$advGroupMap[$advGroupId]['campaign_id'],
                                'media_campaignid'=>$advGroupMap[$advGroupId]['media_campaignid'],
                                'campaign_name'=>$advGroupMap[$advGroupId]['campaign_name'],
                                'advgroup_id'=>$advGroupMap[$advGroupId]['id'],
                                'media_advgroupid'=>$advGroupId,
                                'advgroup_name'=>$advGroupMap[$advGroupId]['advgroup_name'],
                                'media_creativeid'=>$creative['Id'],
                                'media_creativetype'=>$creative['Type'],
                                'title'=>$creative['Headline'],
                                'description1'=>$creative['LongHeadlineString'],
                                'destinationUrl'=>isset($creative['FinalUrls']) ? $creative['FinalUrls'] : '',
                                'displayUrl'=>isset($creative['FinalAppUrls']) ? $creative['FinalAppUrls'] : '',
                                'mobileDestinationUrl'=>isset($creative['FinalMobileUrls']) ? $creative['FinalMobileUrls'] : '',
                                'mobileDisplayUrl'=>isset($creative['FinalMobileUrls']) ? $creative['FinalMobileUrls'] : '',
                                'addTime'=>'',
                                'status'=>$this->_bingCreativeStatusMap($creative['Status'])['statusCode'],
                                'media_status'=>$creative['Status'],
                            ];

                            $decide = $this->insertOrUpdateOrNothing($insertCreative, $oldCreativesMap);
                            if($decide['code'] == 2){
                                $insertID = $this->insert($insertCreative, true);
                                if($insertID){
                                    $insertCreative['id'] = $insertID;
                                    $oldCreativesMap[$insertCreative['media_creativeid']] = $insertCreative;
                                } else {
                                    //TODO 写入失败 记录日志
                                    break;
                                }
                            } elseif($decide['code'] == 1){
                                $updateRes = $this->save($decide['update']);
                                if($updateRes > 0){
                                    $insertCreative['id'] = $decide['update']['id'];
                                    $oldCreativesMap[$insertCreative['media_creativeid']] = $insertCreative;
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
        
        return $oldCreativesMap;
    }
    
    /**
     * 判断是否插入新数据还是更新数据
     * 如果数据未发生更改 则什么都不做
     * @param unknown $insertCreative
     * @param unknown $oldCreativesMap
     *
     * return code == 2 插入
     *        code == 1 更新
     *        code == 0 什么都不做
     */
    private function insertOrUpdateOrNothing($insertCreative, $oldCreativesMap){
        if(!isset($oldCreativesMap[$insertCreative['media_creativeid']])){
            return ['code'=>2,'update'=>[]];
        }
        
        $update = [];
        $compare = $oldCreativesMap[$insertCreative['media_creativeid']];
        foreach ($insertCreative as $k=>$v){
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
     * 转换 360 广告创意的status字段
     * 详细文档可见 https://open.e.360.cn/api/creative_getInfoByIdList.html status字段说明
     * @param string $status
     * @return number
     */
    private function _360CreativeStatusMap($status){
        switch ($status){
            case 'enabled':
                return ['statusCode'=>1];
            case 'paused':
                return ['statusCode'=>0];
        }
        return ['statusCode'=>1];
    }
    
    /**
     * 转换 百度 广告创意的status字段
     * 详细文档可见 https://dev2.baidu.com/content?sceneType=0&pageId=100274&nodeId=352&subhead=  status字段说明
     * @param unknown $status
     * 51 - 有效
     * 52 - 暂停推广
     * 53 - 审核不通过
     * 54 - 待激活
     * 55 - 审核中
     * 56 - 部分无效
     * 57 - 有效-移动URL审核中
     * 59 - 未审核
     * @return number
     */
    private function _baiduCreativeStatusMap($status){
        switch ($status){
            case 51:
                return ['statusCode'=>1,'statusTxt'=>'正常投放'];
            case 52:
                return ['statusCode'=>0,'statusTxt'=>'暂停推广'];
            case 53:
                return ['statusCode'=>0,'statusTxt'=>'暂停推广'];
            case 54:
                return ['statusCode'=>0,'statusTxt'=>'待激活'];
            case 55:
                return ['statusCode'=>0,'statusTxt'=>'审核中'];
            case 56:
                return ['statusCode'=>0,'statusTxt'=>'部分无效'];
            case 57:
                return ['statusCode'=>0,'statusTxt'=>'移动URL审核中'];
            case 59:
                return ['statusCode'=>0,'statusTxt'=>'未审核'];
        }
        return ['statusCode'=>1,'statusTxt'=>'正常投放'];
    }
    
    /**
     * 转换 Bing 广告创意的status字段
     * 详细文档可见 https://learn.microsoft.com/zh-cn/advertising/campaign-management-service/campaignstatus?view=bingads-13
     * Active、BudgetAndManualPaused、BudgetPaused、Deleted、Paused、Suspended
     * @param unknown $status
     * @return number
     */
    private function _bingCreativeStatusMap($status){
        switch ($status){
            case 'Active':
                return ['statusCode'=>1];
            case 'Deleted':
                return ['statusCode'=>0];
            case 'Inactive':
                return ['statusCode'=>0];
            case 'Paused':
                return ['statusCode'=>0];
        }
        return ['statusCode'=>1];
    }
    
    
    /**
     * 修改 360 创意
     */
    public function updateCreative360($accInfo = [], $body = []){
        if(count($body) == 0){
            return;
        }
        $creative360  = new AdvCreative360();
        $_fieldsMap = [
            'id'=>'media_creativeid',
            'status'=>'status'                  //广告组状态
        ];
        $_mapBody = [];
        foreach ($_fieldsMap as $k=>$v){
            if(isset($body[$v])){
                $_mapBody[$k] = $this->_updateAdvGroup360Deco($k, $body[$v]);
            }
        }
        $res = $creative360->updateCreative($accInfo['devkey'], $accInfo['accessToken'], [$_mapBody]);
        if(count($res['failures']) == 0){
            //修改成功 更新数据库
            $body = $this->_updateCreativeDecoDb($body);
            $this->update($body['creative_id'], $body);
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
     * 更新  Baidu 创意
     * @param array $accInfo
     * @param array $body
     * @return void|mixed
     */
    public function updateCreativeBaidu($accInfo = [], $body = []){
        if(count($body) == 0){
            return;
        }
        $_fieldsMap = [
            'creativeId'=>'media_creativeid',
            'pause'=>'status'                   //创意状态
        ];
        $_mapBody = [];
        foreach ($_fieldsMap as $k=>$v){
            if(isset($body[$v])){
                $_mapBody[$k] = $this->_updateCreativeBaiduDeco($k, $body[$v]);
            }
        }
        $_mapBodys = ['creativeTypes'=>[$_mapBody]];
        $creativeBaidu  = new AdvCreativeBaidu();
        $res = $creativeBaidu->updateCreatives($accInfo['username'], $accInfo['accessToken'], $_mapBodys);
        //dump($_mapBodys);
        //dump($res);
        //exit;
        if(count($res['header']['failures']) == 0){
            //修改成功 更新数据库
            $body = $this->_updateCreativeDecoDb($body);
            $this->update($body['creative_id'], $body);
        }
        return $res;
    }
    
    /**
     * 请求百度marketing api的数据修饰
     * @param unknown $k
     * @param unknown $v
     * @return boolean|unknown
     */
    public function _updateCreativeBaiduDeco($k, $v){
        if($k == 'pause'){
            return filter_var($v, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);        //将字符串转为布尔值 然后再取反
        }
        return $v;
    }
    
    /**
     * 更新  Bing 创意
     * @param array $accInfo
     * @param array $body
     * @return void|mixed
     */
    public function updateCreativeBing($accInfo = [], $body = []){
        if(count($body) == 0){
            return;
        }
        $advGroupId = $body['media_advgroupid'];
        $updateData = $this->_updateCreativeBingDeco($body);
        $creativeBing  = new AdvCreativeBing();
        $res = $creativeBing->updateCreatives($accInfo['devkey'], $accInfo['accessToken'], $accInfo['media_account_id'], $advGroupId, $updateData);
        //dump($_mapBodys);
        //dump($res);
        //exit;
        if(isset($res['PartialErrors']) && count($res['PartialErrors']) == 0){
            //修改成功 更新数据库
            $body = $this->_updateCreativeDecoDb($body);
            $updateRes = $this->update($body['creative_id'], $body);
        }
        return $res;
    }
    
    /**
     * 请求 Bing marketing api的数据修饰
     * @param array $arr
     * @return boolean|unknown
     */
    public function _updateCreativeBingDeco($arr){
        $_body = ['Id'=>$arr['media_creativeid']]; //必须是整形
        if (isset($arr['status'])){
            if(intval($arr['status']) > 0){
                $_body['Status'] = 'Paused';
            } else {
                $_body['Status'] = 'Active';
            }
        }
        $_body['Type'] = $arr['media_creativetype'];        //一定要加上 Type参数
        return $_body;
    }
    
    /**
     * 数据入库时的修饰
     * @param unknown $k
     * @param unknown $v
     * @return boolean|unknown
     */
    public function _updateCreativeDecoDb($data){
        if(isset($data['status'])){
            $data['status'] = (string)(1 - intval($data['status']));
        }
        return $data;
    }
}
