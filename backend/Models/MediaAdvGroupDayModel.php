<?php
/**
 * 广告组报表模型
 */
namespace App\Models;

use App\Libraries\MarketingApi360\SEM\AdvReport\Group as GroupReport360;
use App\Libraries\MarketingApiBaidu\SEM\AdvReport\Group as GroupReportBaidu;
use App\Libraries\MarketingApiBing\SEM\AdvReport\Group as GroupReportBing;
use CodeIgniter\CLI\CLI;

class MediaAdvGroupDayModel extends BaseModel
{
    protected $table = 'dev_report_group_day';
    
    
    protected $allowedFields = [
        'account_id',
        'campaign_id',
        'media_campaignid',
        'advgroup_id',
        'media_advgroupid',
        'consume',
        'shows',
        'clicks',
        'converts',
        'convertsCost',
        'device',
        'uid',
        'sdate'
    ];
    
    protected $needUpdateFields = [
        'consume',
        'shows',
        'clicks',
        'converts',
        'convertsCost'
    ];
    
    /**
     * 查找数据库中的旧数据
     * @param unknown $uid
     * @param unknown $startDate
     * @param unknown $endDate
     * @return array|unknown
     */
    public function getOldReports($accounts, $startDate, $endDate){
       $res = [];
       $accountIds = array_keys($accounts);
       if(count($accountIds) > 0){
           $_aids = implode(',', $accountIds);
           $reports = $this->where("account_id IN(".$_aids.") AND sdate>='".$startDate."' AND sdate<='".$endDate."'")->findAll();
           if(count($reports) > 0){
               foreach ($reports as $v){
                   if($v['device'] == 1){
                        $res[$v['account_id']]['mobile'][$v['media_advgroupid']][$v['sdate']] = $v;
                   } elseif ($v['device'] == 2){
                        $res[$v['account_id']]['computer'][$v['media_advgroupid']][$v['sdate']] = $v;
                   } elseif ($v['device'] == 3){
                       $res[$v['account_id']]['others'][$v['media_advgroupid']][$v['sdate']] = $v;
                   }
               }
           }
       }
       return $res;
    }
    
    /**
     * 360
     * 获取广告组维度投放 消耗 转化等数据
     */
    public function getGroupReport360($account, $startDate, $endDate, $oldReports, $advGroups){
        $oldComputerReport = isset($oldReports[$account['id']]['computer']) ? $oldReports[$account['id']]['computer'] : [];
        $oldMobileReport = isset($oldReports[$account['id']]['mobile']) ? $oldReports[$account['id']]['mobile'] : [];
        $groupsMap = array_column($advGroups, null, 'media_advgroupid');
        //dump($groupsMap);
        $result = [];
        //对日期进行校验
        $timeStamps = $this->checkDate($startDate, $endDate);
        $groupReport360 = new GroupReport360();
        if($timeStamps['endST'] == $timeStamps['todayST']){
            //查询今日的实时数据
            $_pcTodayReport = $groupReport360->reportToday($account['devkey'], $account['accessToken'], 'computer');
            //CLI::print('today:'.json_encode($_pcTodayReport));
            //dump($_pcTodayReport);
            //exit;
            $this->_handleReport360($account, $_pcTodayReport, $groupsMap, $oldComputerReport, 2);
            $_mobileTodayReport = $groupReport360->reportToday($account['devkey'], $account['accessToken'], 'mobile');
            $this->_handleReport360($account, $_mobileTodayReport, $groupsMap, $oldMobileReport, 1);
        }
        $newEndTimestamp = $timeStamps['endST'] - 24 * 3600;
        do {
            if($timeStamps['startST'] <= $newEndTimestamp){
                //查询历史结算数据
                $newEndDate = date('Y-m-d', $newEndTimestamp);
                $_pcHistoryReport = $groupReport360->reportHistory($account['devkey'], $account['accessToken'], $startDate, $newEndDate, 'computer');
                //CLI::print('history:'.json_encode($_pcHistoryReport));
                //靠近凌晨的时间点  可能前一天的报表还未生成 需要再往前推一天 即只能获取到前天的报表数据
                if(isset($_pcHistoryReport['failures']) && $_pcHistoryReport['failures'][0]['code'] == 70109){
                    $newEndTimestamp = $timeStamps['endST'] - 24 * 3600 * 2;
                    continue;
                }
                $this->_handleReport360($account, $_pcHistoryReport, $groupsMap, $oldComputerReport, 2);
                $_mobileHistoryReport = $groupReport360->reportHistory($account['devkey'], $account['accessToken'], $startDate, $newEndDate, 'mobile');
                $this->_handleReport360($account, $_mobileHistoryReport, $groupsMap, $oldMobileReport, 1);
            }
            break;
        } while(true);
        //dump($oldComputerReport);
        return ['computer'=>$oldComputerReport,'mobile'=>$oldMobileReport];
    }
    
    /**
     * 处理从360广告组维度接口获取的数据报表
     * @param array $account          帐户信息
     * @param array $reportGetFromApi 从360接口获取的报表数据
     * @param array $groupsMap        广告组映射数据
     * @param array $oldReportFromDb  从数据库查找的广告组报表数据
     *                                该数据需要实时修改 所以使用引用传递
     *
     * @param int   $device           设备      1 mobile 2 computer
     */
    private function _handleReport360($account, $reportGetFromApi, $groupsMap, &$oldReportFromDb, $device){
        if(isset($reportGetFromApi['failures'])){
            log_message('info','_handleReport360 fail');
        } else {
            $report = $reportGetFromApi['groupList'];
            if(count($report) > 0){
                foreach ($report as $v){
                    if(!isset($groupsMap[$v['groupId']])){
                        //获取到该推广计划的报表数据 但是该推广计划的基础信息还未入库
                        //则记录错误日志
                        log_message('info','advgroup error 100');
                        continue;
                    }
                    $insertReport = [
                        'account_id'=>$account['id'],
                        'campaign_id'=>$groupsMap[$v['groupId']]['campaign_id'],
                        'media_campaignid'=>$v['campaignId'],
                        'advgroup_id'=>$groupsMap[$v['groupId']]['id'],
                        'media_advgroupid'=>$v['groupId'],
                        'consume'=>intval($v['totalCost']*100),
                        'shows'=>$v['views'],
                        'clicks'=>$v['clicks'],
                        'converts'=>$v['converts'],
                        'convertsCost'=>intval($v['convertsCost']*100),
                        'device'=>$device,
                        'uid'=>$account['uid'],
                        'sdate'=>$v['date']
                    ];
                    $decide = $this->insertOrUpdateOrNothing($insertReport, $oldReportFromDb, 'media_advgroupid');
                }
            }
        }
    }
    
    /**
     * baidu
     * @param array $accInfo
     * @param array $campaigns
     */
    public function getGroupReportBaidu($account, $startDate, $endDate, $oldReports, $advGroups){
        $oldComputerReport = isset($oldReports[$account['id']]['computer']) ? $oldReports[$account['id']]['computer'] : [];
        $oldMobileReport = isset($oldReports[$account['id']]['mobile']) ? $oldReports[$account['id']]['mobile'] : [];
        $groupsMap = array_column($advGroups, null, 'media_advgroupid');
        //dump($groupsMap);
        //exit;
        $result = [];
        $timeStamps = $this->checkDate($startDate, $endDate, 730);
        $groupReportBaidu = new GroupReportBaidu();
        $_report = $groupReportBaidu->report($account['username'], $account['accessToken'], $startDate, $endDate);
        $this->_handleReportBaidu($account, $_report, $groupsMap, $oldComputerReport, $oldMobileReport);
        
        return ['computer'=>$oldComputerReport,'mobile'=>$oldMobileReport];
    }
    
    /**
     * 对日期进行校验
     *      
     * 不同的媒体渠道对查询日期有不同的要求
     * 例如 360的查询日期跨度不能超过90天
     *     百度的查询日期跨度不能超过730天
     */
    /* private function checkDate($startDate, $endDate, $days = 90){
        //今日起始时间戳
        $todayStartTimestamp = strtotime(date('Y-m-d',time()));
        //查询起始日期起始时间戳
        $startDateStartTimestamp = strtotime(date('Y-m-d', strtotime($startDate)));
        //查询结束日期起始时间戳
        $endDateStartTimestamp = strtotime(date('Y-m-d', strtotime($endDate)));
        if(($startDateStartTimestamp > $endDateStartTimestamp) || ($startDateStartTimestamp > $todayStartTimestamp) || ($endDateStartTimestamp > $todayStartTimestamp)){
            _json(['code'=>199,'msg'=>'查询日期错误,不能选择超过当天的时间'], 1);
        }
        if($todayStartTimestamp - $startDateStartTimestamp > $days * 24 * 3600){
            _json(['code'=>198,'msg'=>'查询跨度不能超过'.$days.'天'], 1);
        }
        return ['todayST'=>$todayStartTimestamp,'startST'=>$startDateStartTimestamp,'endST'=>$endDateStartTimestamp];
    } */
    
   
    
    /**
     * 处理从baidu推广单元(广告组)维度接口获取的数据报表
     * @param array $account          帐户信息
     * @param array $reportGetFromApi 从baidu接口获取的报表数据
     * @param array $groupsMap        baidu推广单元(广告组)映射
     * @param array $oldComputerReportFromDb
     *                                从数据库查找的推广单元(广告组)报表数据(PC端)
     *                                该数据需要实时修改 所以使用引用传递
     * @param array $oldMobileReportFromDb
     *                                从数据库查找的推广单元(广告组)报表数据(移动端)
     *                                该数据需要实时修改 所以使用引用传递
     */
    private function _handleReportBaidu($account, $reportGetFromApi, $groupsMap, &$oldComputerReportFromDb, &$oldMobileReportFromDb){
        //dump($reportGetFromApi);
        //dump($groupsMap);
        //exit;
        if(count($reportGetFromApi['header']['failures']) > 0){
            log_message('info','_handleReportBaidu fail');
        } else {
            $report = isset($reportGetFromApi['body']['data'][0]['rows']) ? $reportGetFromApi['body']['data'][0]['rows'] : [];
            if(count($report) > 0){
                foreach ($report as $v){
                    if(!isset($groupsMap[$v['adGroupId']])){
                        //获取到该推广单元(广告组)的报表数据 但是该推广单元(广告组)的基础信息还未入库
                        //则记录错误日志
                        log_message('info','advgroup error 100');
                        continue;
                    }
                    $insertReport = [
                        'account_id'=>$account['id'],
                        'campaign_id'=>$groupsMap[$v['adGroupId']]['campaign_id'],
                        'media_campaignid'=>$v['campaignId'],
                        'advgroup_id'=>$groupsMap[$v['adGroupId']]['id'],
                        'media_advgroupid'=>$v['adGroupId'],
                        'consume'=>intval($v['cost']*100),
                        'shows'=>$v['impression'],
                        'clicks'=>$v['click'],
                        'converts'=>0,              //todo 百度的转化量需要根据传入的指标来获取
                        'convertsCost'=>0,
                        'device'=>$v['device'] == '移动设备' ? 1 : 2,
                        'uid'=>$account['uid'],
                        'sdate'=>$v['date']
                    ];
                    if($v['device'] == '移动设备'){
                        $oldReportFromDb = &$oldMobileReportFromDb;
                    } else {
                        $oldReportFromDb = &$oldComputerReportFromDb;
                    }
                    $decide = $this->insertOrUpdateOrNothing($insertReport, $oldReportFromDb, 'media_advgroupid');
                    /* if($decide['code'] == 2){
                        $insertID = $this->insert($insertReport, true);
                        if($insertID){
                            $insertReport['id'] = $insertID;
                            $oldReportFromDb[$insertReport['media_advgroupid']][$insertReport['sdate']] = $insertReport;
                        } else {
                            //TODO 写入失败 记录日志
                            continue;
                        }
                    } elseif($decide['code'] == 1){
                        $updateRes = $this->save($decide['update']);
                        if($updateRes > 0){
                            $insertReport['id'] = $decide['update']['id'];
                            $oldReportFromDb[$insertReport['media_advgroupid']][$insertReport['sdate']] = $insertReport;
                        } else {
                            //TODO 更新失败 记录日志
                            continue;
                        }
                    } */
                }
            }
            //dump($report);
        }
    }
    
    /**
     *
     * @param array $accInfo
     * @param array $campaigns
     */
    public function getGroupReportBing($account, $startDate, $endDate, $oldReports, $advGroups){
        $oldComputerReport = isset($oldReports[$account['id']]['computer']) ? $oldReports[$account['id']]['computer'] : [];
        $oldMobileReport = isset($oldReports[$account['id']]['mobile']) ? $oldReports[$account['id']]['mobile'] : [];
        $oldOthersReport = isset($oldReports[$account['id']]['others']) ? $oldReports[$account['id']]['others'] : [];
        $groupsMap = array_column($advGroups, null, 'media_advgroupid');
        //dump($groupsMap);
        //exit;
        $result = [];
        //$timeStamps = $this->checkDate($startDate, $endDate);
        $groupReportBing = new GroupReportBing();
        $_report = $groupReportBing->report($account['devkey'], $account['accessToken'], $account['media_account_id'], $startDate, $endDate);
        $this->_handleReportBing($account, $_report, $groupsMap, $oldComputerReport, $oldMobileReport, $oldOthersReport);
        
        return ['computer'=>$oldComputerReport,'mobile'=>$oldMobileReport, 'others'=>$oldOthersReport];
    }
    
    /**
     * 处理从 Bing 广告组维度接口获取的数据报表
     * @param array $account          帐户信息
     * @param array $reportGetFromApi 从 Bing 接口获取的报表数据
     * @param array $groupsMap        Bing 广告组信息（以广告组id作为key）
     * @param array $oldComputerReportFromDb
     *                                从数据库查找的推广单元(广告组)报表数据(PC端)
     *                                该数据需要实时修改 所以使用引用传递
     * @param array $oldMobileReportFromDb
     *                                从数据库查找的推广单元(广告组)报表数据(移动端)
     *                                该数据需要实时修改 所以使用引用传递
     * @param array $oldMobileReportFromDb
     *                                从数据库查找的广告组报表数据(其他)
     *                                该数据需要实时修改 所以使用引用传递
     */
    private function _handleReportBing($account, $reportGetFromApi, $groupsMap, &$oldComputerReportFromDb, &$oldMobileReportFromDb, &$oldOthersReportFromDb){
        if(count($reportGetFromApi) > 0){
            $_map = [
                'Computer'=>['deviceTypeID'=>2,'oldData'=>&$oldComputerReportFromDb],
                'Smartphone'=>['deviceTypeID'=>1,'oldData'=>&$oldMobileReportFromDb],
                'Tablet'=>['deviceTypeID'=>3,'oldData'=>&$oldOthersReportFromDb]
            ];
            //dump($reportGetFromApi);
            foreach ($reportGetFromApi as $v){
                if(empty($account['balance'])){
                    //获取到该帐户的报表数据 但是该帐户的基础信息还未入库
                    //则记录错误日志
                    log_message('info','account error 100');
                    continue;
                }
                
                $insertReport = [
                    'account_id'=>$account['id'],
                    'campaign_id'=>$groupsMap[$v['AdGroupId']]['id'],
                    'media_campaignid'=>$v['CampaignId'],
                    'advgroup_id'=>$groupsMap[$v['AdGroupId']]['id'],
                    'media_advgroupid'=>$v['AdGroupId'],
                    'uid'=>$account['uid'],
                    'consume'=>intval($v['Spend']*100),
                    'shows'=>$v['Impressions'],
                    'clicks'=>$v['Clicks'],
                    'device'=>$_map[$v['DeviceType']]['deviceTypeID'],
                    'sdate'=>$v['TimePeriod']
                ];
                //-2写入失败 -1更新失败 0无动作 1更新成功 2写入成功
                $this->insertOrUpdateOrNothing($insertReport, $_map[$v['DeviceType']]['oldData'], 'media_advgroupid');
            }
        }
    }
    
    /**
     * TODO 批量插入或更新数据
     * 判断是否插入新数据还是更新数据
     * 如果数据未发生更改 则什么都不做
     * @param unknown $insertGroup
     * @param unknown $oldAdvGroups
     *
     * return code == 2 插入
     *        code == 1 更新
     *        code == 0 什么都不做
     */
    /* private function insertOrUpdateOrNothing($insertReport, $oldReport){
        if(!isset($oldReport[$insertReport['media_advgroupid']][$insertReport['sdate']])){
            //exit;
            return ['code'=>2,'update'=>[]];
        }
        
        $update = [];
        $compare = $oldReport[$insertReport['media_advgroupid']][$insertReport['sdate']];
        foreach ($insertReport as $k=>$v){
            //慎用  !== 比较   数据库类型为int 但PDO驱动查询会全部转为string类型
            //此外数据库字段类型为json的话 会自动伸缩json结构 使得无法使用 != 操作符来进行字符比对 解决方案 数据表采用string字段类型
            if(in_array($k, $this->needUpdateFields) && ($v != $compare[$k])){
                //dump($compare);
                //exit;
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
    } */
}