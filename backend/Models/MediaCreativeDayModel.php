<?php
/**
 * 推广计划报表模型
 */
namespace App\Models;

use App\Libraries\MarketingApi360\SEM\AdvReport\Creative as CreativeReport360;
use App\Libraries\MarketingApiBaidu\SEM\AdvReport\Creative as CreativeReportBaidu;
use App\Libraries\MarketingApiBing\SEM\AdvReport\Creative as CreativeReportBing;

class MediaCreativeDayModel extends BaseModel
{
    protected $table = 'dev_report_creative_day';
    
    protected $allowedFields = [
        'account_id',
        'campaign_id',
        'media_campaignid',
        'advgroup_id',
        'media_advgroupid',
        'creative_id',
        'media_creativeid',
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
                        $res[$v['account_id']]['mobile'][$v['media_creativeid']][$v['sdate']] = $v;
                   } else {
                        $res[$v['account_id']]['computer'][$v['media_creativeid']][$v['sdate']] = $v;
                   }
               }
           }
       }
       return $res;
    }
    
    /**
     * 360
     * 获取创意维度投放 消耗 转化等数据
     */
    public function getCreativeReport360($account, $startDate, $endDate, $oldReports, $creatives){
        $oldComputerReport = isset($oldReports[$account['id']]['computer']) ? $oldReports[$account['id']]['computer'] : [];
        $oldMobileReport = isset($oldReports[$account['id']]['mobile']) ? $oldReports[$account['id']]['mobile'] : [];
        $creativesMap = array_column($creatives, null, 'media_creativeid');
        //dump($groupsMap);
        //exit;
        $result = [];
        //对日期进行校验
        $timeStamps = $this->checkDate($startDate, $endDate);
        $creativeReport360 = new CreativeReport360();
        if($timeStamps['endST'] == $timeStamps['todayST']){
            //查询今日的实时数据
            $_pcTodayReport = $creativeReport360->reportToday($account['devkey'], $account['accessToken'], 'computer');
            $this->_handleReport360($account, $_pcTodayReport, $creativesMap, $oldComputerReport, 2);
            $_mobileTodayReport = $creativeReport360->reportToday($account['devkey'], $account['accessToken'], 'mobile');
            $this->_handleReport360($account, $_mobileTodayReport, $creativesMap, $oldMobileReport, 1);
        }
        $newEndTimestamp = $timeStamps['endST'] - 24 * 3600;
        do {
            if($timeStamps['startST'] <= $newEndTimestamp){
                //查询历史结算数据
                $newEndDate = date('Y-m-d', $newEndTimestamp);
                $_pcHistoryReport = $creativeReport360->reportHistory($account['devkey'], $account['accessToken'], $startDate, $newEndDate, 'computer');
                //靠近凌晨的时间点  可能前一天的报表还未生成 需要再往前推一天 即只能获取到前天的报表数据
                if(isset($_pcHistoryReport['failures']) && $_pcHistoryReport['failures'][0]['code'] == 70109){
                    $newEndTimestamp = $timeStamps['endST'] - 24 * 3600 * 2;
                    continue;
                }
                $this->_handleReport360($account, $_pcHistoryReport, $creativesMap, $oldComputerReport, 2);
                $_mobileHistoryReport = $creativeReport360->reportHistory($account['devkey'], $account['accessToken'], $startDate, $newEndDate, 'mobile');
                $this->_handleReport360($account, $_mobileHistoryReport, $creativesMap, $oldMobileReport, 1);
            }
            break;
        } while(true);
        //dump($oldComputerReport);
        return ['computer'=>$oldComputerReport,'mobile'=>$oldMobileReport];
    }
    
    /**
     * 处理从360创意维度接口获取的数据报表
     * @param array $account          帐户信息
     * @param array $reportGetFromApi 从360接口获取的创意报表数据
     * @param array $creativesMap     创意映射数据
     * @param array $oldReportFromDb  从数据库查找的创意报表数据
     *                                该数据需要实时修改 所以使用引用传递
     *
     * @param int   $device           设备      1 mobile 2 computer
     */
    private function _handleReport360($account, $reportGetFromApi, $creativesMap, &$oldReportFromDb, $device){
        if(isset($reportGetFromApi['failures'])){
            log_message('info','_handleReport360 fail');
        } else {
            $report = $reportGetFromApi['creativeList'];
            if(count($report) > 0){
                foreach ($report as $v){
                    if(!isset($creativesMap[$v['creativeId']])){
                        //获取到该创意的报表数据 但是该创意的基础信息还未入库
                        //则记录错误日志
                        log_message('info','creative error 100');
                        continue;
                    }
                    $insertReport = [
                        'account_id'=>$account['id'],
                        'campaign_id'=>$creativesMap[$v['creativeId']]['campaign_id'],
                        'media_campaignid'=>$v['campaignId'],
                        'advgroup_id'=>$creativesMap[$v['creativeId']]['advgroup_id'],
                        'media_advgroupid'=>$v['groupId'],
                        'creative_id'=>$creativesMap[$v['creativeId']]['id'],
                        'media_creativeid'=>$v['creativeId'],
                        'consume'=>intval($v['totalCost']*100),
                        'shows'=>$v['views'],
                        'clicks'=>$v['clicks'],
                        'device'=>$device,
                        'uid'=>$account['uid'],
                        'sdate'=>$v['date']
                    ];
                    //-2写入失败 -1更新失败 0无动作 1更新成功 2写入成功
                    $decideRes = $this->insertOrUpdateOrNothing($insertReport, $oldReportFromDb, 'media_creativeid');
                    if($decideRes < 0){
                        continue;
                    }
                }
            }
        }
    }
    
    /**
     * 百度
     * @param array $accInfo
     * @param array $campaigns
     */
    public function getCreativeReportBaidu($account, $startDate, $endDate, $oldReports, $creatives){
        $oldComputerReport = isset($oldReports[$account['id']]['computer']) ? $oldReports[$account['id']]['computer'] : [];
        $oldMobileReport = isset($oldReports[$account['id']]['mobile']) ? $oldReports[$account['id']]['mobile'] : [];
        $creativesMap = array_column($creatives, null, 'media_creativeid');
        //dump($groupsMap);
        //exit;
        $result = [];
        $this->checkDate($startDate, $endDate, 730);
        $creativeReportBaidu = new CreativeReportBaidu();
        $_creative_report = $creativeReportBaidu->report($account['username'], $account['accessToken'], $startDate, $endDate);
        $this->_handleReportBaidu($account, $_creative_report, $creativesMap, $oldComputerReport, $oldMobileReport);
        
        return ['computer'=>$oldComputerReport,'mobile'=>$oldMobileReport];
    }
    
    
    /**
     * 处理从baidu创意维度接口获取的数据报表
     * @param array $account          帐户信息
     * @param array $reportGetFromApi 从baidu接口获取的报表数据
     * @param array $creativesMap     baidu 创意数据映射
     * @param array $oldComputerReportFromDb
     *                                从数据库查找的创意报表数据(PC端)
     *                                该数据需要实时修改 所以使用引用传递
     * @param array $oldMobileReportFromDb
     *                                从数据库查找的创意报表数据(移动端)
     *                                该数据需要实时修改 所以使用引用传递
     */
    private function _handleReportBaidu($account, $reportGetFromApi, $creativesMap, &$oldComputerReportFromDb, &$oldMobileReportFromDb){
        if(count($reportGetFromApi['header']['failures']) > 0){
            log_message('info','_handleReportBaidu fail');
        } else {
            $report = isset($reportGetFromApi['body']['data'][0]['rows']) ? $reportGetFromApi['body']['data'][0]['rows'] : [];
            if(count($report) > 0){
                foreach ($report as $v){
                    //dump($creativesMap);
                    //dump($v);
                    //exit;
                    if(!isset($creativesMap[$v['ideaId']])){
                        //获取到该创意的报表数据 但是该创意的基础信息还未入库
                        //则记录错误日志
                        log_message('info','creative error 100');
                        continue;
                    }
                    $insertReport = [
                        'account_id'=>$account['id'],
                        'campaign_id'=>$creativesMap[$v['ideaId']]['campaign_id'],
                        'media_campaignid'=>$v['campaignId'],
                        'advgroup_id'=>$creativesMap[$v['ideaId']]['advgroup_id'],
                        'media_advgroupid'=>$v['adGroupId'],
                        'creative_id'=>$creativesMap[$v['ideaId']]['id'],
                        'media_creativeid'=>$v['ideaId'],
                        'consume'=>intval($v['cost']*100),
                        'shows'=>$v['impression'],
                        'clicks'=>$v['click'],
                        'device'=>$v['device'] == '移动设备' ? 1 : 2,
                        'uid'=>$account['uid'],
                        'sdate'=>$v['date']
                    ];
                    if($insertReport['device'] == 1){
                        $oldReportFromDb = &$oldMobileReportFromDb;
                    } else {
                        $oldReportFromDb = &$oldComputerReportFromDb;
                    }
                    //-2写入失败 -1更新失败 0无动作 1更新成功 2写入成功
                    $decideRes = $this->insertOrUpdateOrNothing($insertReport, $oldReportFromDb, 'media_creativeid');
                    if($decideRes < 0){
                        continue;
                    }
                }
            }
        }
    }
    
    /**
     * Bing
     * @param array $accInfo
     * @param array $campaigns
     */
    public function getCreativeReportBing($account, $startDate, $endDate, $oldReports, $creatives){
        $oldComputerReport = isset($oldReports[$account['id']]['computer']) ? $oldReports[$account['id']]['computer'] : [];
        $oldMobileReport = isset($oldReports[$account['id']]['mobile']) ? $oldReports[$account['id']]['mobile'] : [];
        $oldOthersReport = isset($oldReports[$account['id']]['others']) ? $oldReports[$account['id']]['others'] : [];
        $creativesMap = array_column($creatives, null, 'media_creativeid');
        //dump($groupsMap);
        //exit;
        $result = [];
        //$this->checkDate($startDate, $endDate, 730);
        $creativeReportBing = new CreativeReportBing();
        $_creative_report = $creativeReportBing->report($account['devkey'], $account['accessToken'], $account['media_account_id'], $startDate, $endDate);
        $this->_handleReportBing($account, $_creative_report, $creativesMap, $oldComputerReport, $oldMobileReport, $oldOthersReport);
        
        return ['computer'=>$oldComputerReport,'mobile'=>$oldMobileReport, 'others'=>$oldOthersReport];
    }
    
    /**
     * 处理从 Bing 创意维度接口获取的数据报表
     * @param array $account          帐户信息
     * @param array $reportGetFromApi 从baidu接口获取的报表数据
     * @param array $creativesMap     baidu 创意数据映射
     * @param array $oldComputerReportFromDb
     *                                从数据库查找的创意报表数据(PC端)
     *                                该数据需要实时修改 所以使用引用传递
     * @param array $oldMobileReportFromDb
     *                                从数据库查找的创意报表数据(移动端)
     *                                该数据需要实时修改 所以使用引用传递
     */
    private function _handleReportBing($account, $reportGetFromApi, $creativesMap, &$oldComputerReportFromDb, &$oldMobileReportFromDb, &$oldOthersReportFromDb){
        if(count($reportGetFromApi) > 0){
            $_map = [
                'Computer'=>['deviceTypeID'=>2,'oldData'=>&$oldComputerReportFromDb],
                'Smartphone'=>['deviceTypeID'=>1,'oldData'=>&$oldMobileReportFromDb],
                'Tablet'=>['deviceTypeID'=>3,'oldData'=>&$oldOthersReportFromDb]
            ];
            //dump($reportGetFromApi);
            //exit;
            foreach ($reportGetFromApi as $v){
                if(!isset($creativesMap[$v['AdId']])){
                    //获取到该创意的报表数据 但是该创意的基础信息还未入库
                    //则记录错误日志
                    log_message('info','creative error 100');
                    continue;
                }
                
                $insertReport = [
                    'account_id'=>$account['id'],
                    'campaign_id'=>$creativesMap[$v['AdId']]['campaign_id'],
                    'media_campaignid'=>$v['CampaignId'],
                    'advgroup_id'=>$creativesMap[$v['AdId']]['advgroup_id'],
                    'media_advgroupid'=>$v['AdGroupId'],
                    'creative_id'=>$creativesMap[$v['AdId']]['id'],
                    'media_creativeid'=>$v['AdId'],
                    'consume'=>intval($v['Spend']*100),
                    'shows'=>$v['Impressions'],
                    'clicks'=>$v['Clicks'],
                    'device'=>$_map[$v['DeviceType']]['deviceTypeID'],
                    'uid'=>$account['uid'],
                    'sdate'=>$v['TimePeriod']
                ];
                //-2写入失败 -1更新失败 0无动作 1更新成功 2写入成功
                $this->insertOrUpdateOrNothing($insertReport, $_map[$v['DeviceType']]['oldData'], 'media_creativeid');
            }
        }
    }
    
}