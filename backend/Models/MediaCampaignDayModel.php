<?php
/**
 * 推广计划报表模型
 */
namespace App\Models;

use App\Libraries\MarketingApi360\SEM\AdvReport\Campaign as CampaignReport360;
use App\Libraries\MarketingApiBaidu\SEM\AdvReport\Campaign as CampaignReportBaidu;
use App\Libraries\MarketingApiBing\SEM\AdvReport\Campaign as CampaignReportBing;
use CodeIgniter\CLI\CLI;

class MediaCampaignDayModel extends BaseModel
{
    protected $table = 'dev_report_campaign_day';
    
    
    protected $allowedFields = [
        'account_id',
        'campaign_id',
        'media_campaignid',
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
    
    public function getOldReports($accounts, $startDate, $endDate){
       $res = [];
       $accountIds = array_keys($accounts);
       if(count($accountIds) > 0){
           $_aids = implode(',', $accountIds);
           $reports = $this->where("account_id IN(".$_aids.") AND sdate>='".$startDate."' AND sdate<='".$endDate."'")->findAll();
           
           if(count($reports) > 0){
               foreach ($reports as $v){
                   if($v['device'] == 1){
                        $res[$v['account_id']]['mobile'][$v['media_campaignid']][$v['sdate']] = $v;
                   } elseif ($v['device'] == 2) {
                        $res[$v['account_id']]['computer'][$v['media_campaignid']][$v['sdate']] = $v;
                   } elseif ($v['device'] == 3) {
                        $res[$v['account_id']]['others'][$v['media_campaignid']][$v['sdate']] = $v;
                   }
               }
           }
       }
       return $res;
    }
    
    /**
     * 360
     * 获取推广计划维度投放 消耗 转化等数据
     */
    public function getCampaignReport360($account, $startDate, $endDate, $oldReports, $campaigns){
        $oldComputerReport = isset($oldReports[$account['id']]['computer']) ? $oldReports[$account['id']]['computer'] : [];
        $oldMobileReport = isset($oldReports[$account['id']]['mobile']) ? $oldReports[$account['id']]['mobile'] : [];
        $campaignsMap = array_column($campaigns, null, 'media_campaignid');

        $result = [];

        //do {
            //对日期进行校验
            $timeStamps = $this->checkDate($startDate, $endDate);
            $campaignReport360 = new CampaignReport360();
            if($timeStamps['endST'] == $timeStamps['todayST']){
                //查询今日的实时数据
                $_pcTodayReport = $campaignReport360->reportToday($account['devkey'], $account['accessToken'], 'computer');
                //CLI::print('today:'.json_encode($_pcTodayReport));
                //dump($_pcTodayReport);
                $this->_handleReport360($account, $_pcTodayReport, $campaignsMap, $oldComputerReport, 2);
                $_mobileTodayReport = $campaignReport360->reportToday($account['devkey'], $account['accessToken'], 'mobile');
                $this->_handleReport360($account, $_mobileTodayReport, $campaignsMap, $oldMobileReport, 1);
            }
            $newEndTimestamp = $timeStamps['endST'] - 24 * 3600;
            do {
                if($timeStamps['startST'] <= $newEndTimestamp){
                    //查询历史结算数据
                    $newEndDate = date('Y-m-d', $newEndTimestamp);
                    $_pcHistoryReport = $campaignReport360->reportHistory($account['devkey'], $account['accessToken'], $startDate, $newEndDate, 'computer');
                    //CLI::print('history:'.json_encode($_pcHistoryReport));
                    //靠近凌晨的时间点  可能前一天的报表还未生成 需要再往前推一天 即只能获取到前天的报表数据
                    if(isset($_pcHistoryReport['failures']) && $_pcHistoryReport['failures'][0]['code'] == 70109){
                        $newEndTimestamp = $timeStamps['endST'] - 24 * 3600 * 2;
                        continue;
                    }
                    $this->_handleReport360($account, $_pcHistoryReport, $campaignsMap, $oldComputerReport, 2);
                    $_mobileHistoryReport = $campaignReport360->reportHistory($account['devkey'], $account['accessToken'], $startDate, $newEndDate, 'mobile');
                    $this->_handleReport360($account, $_mobileHistoryReport, $campaignsMap, $oldMobileReport, 1);
                }
                break;
            } while(true);
        //} while(false);
        
        //dump($oldComputerReport);
        return ['computer'=>$oldComputerReport,'mobile'=>$oldMobileReport];
    }
    
    /**
     * 处理从360推广计划维度接口获取的数据报表
     * @param array $account          帐户信息
     * @param array $reportGetFromApi 从360接口获取的报表数据
     * @param array $campaignsMap     推广计划映射
     * @param array $oldReportFromDb  从数据库查找的推广计划报表数据
     *                                该数据需要实时修改 所以使用引用传递
     *
     * @param int   $device           设备      1 mobile 2 computer
     */
    private function _handleReport360($account, $reportGetFromApi, $campaignsMap, &$oldReportFromDb, $device){
        if(isset($reportGetFromApi['failures'])){
            log_message('info','_handleReport360 fail');
        } else {
            $report = $reportGetFromApi['campaignList'];
            if(count($report) > 0){
                foreach ($report as $v){
                    if(!isset($campaignsMap[$v['campaignId']])){
                        //获取到该推广计划的报表数据 但是该推广计划的基础信息还未入库
                        //则记录错误日志
                        log_message('info','campaign error 100');
                        continue;
                    }
                    $insertReport = [
                        'account_id'=>$account['id'],
                        'campaign_id'=>$campaignsMap[$v['campaignId']]['id'],
                        'media_campaignid'=>$v['campaignId'],
                        'consume'=>intval($v['totalCost']*100),
                        'shows'=>$v['views'],
                        'clicks'=>$v['clicks'],
                        'converts'=>$v['converts'],
                        'convertsCost'=>intval($v['convertsCost']*100),
                        'device'=>$device,
                        'uid'=>$account['uid'],
                        'sdate'=>$v['date']
                    ];
                    //CLI::print(json_encode($insertReport));
                    $decideCode = $this->insertOrUpdateOrNothing($insertReport, $oldReportFromDb, 'media_campaignid');
                    /* if($decide['code'] == 2){
                        $insertID = $this->insert($insertReport, true);
                        if($insertID){
                            $insertReport['id'] = $insertID;
                            $oldReportFromDb[$insertReport['media_campaignid']][$insertReport['sdate']] = $insertReport;
                        } else {
                            //TODO 写入失败 记录日志
                            continue;
                        }
                    } elseif($decide['code'] == 1){
                        $updateRes = $this->save($decide['update']);
                        if($updateRes > 0){
                            $insertReport['id'] = $decide['update']['id'];
                            $oldReportFromDb[$insertReport['media_campaignid']][$insertReport['sdate']] = $insertReport;
                        } else {
                            //TODO 更新失败 记录日志
                            continue;
                        }
                    } */
                }
            }
        }
    }
    
    /**
     * baidu 广告计划投放报表数据
     * @param array $accInfo
     * @param array $campaigns
     */
    public function getCampaignReportBaidu($account, $startDate, $endDate, $oldReports, $campaigns){
        $oldComputerReport = isset($oldReports[$account['id']]['computer']) ? $oldReports[$account['id']]['computer'] : [];
        $oldMobileReport = isset($oldReports[$account['id']]['mobile']) ? $oldReports[$account['id']]['mobile'] : [];
        $campaignsMap = array_column($campaigns, null, 'media_campaignid');
        
        //对日期进行校验
        //$timeStamps = $this->checkDate($startDate, $endDate, 730);
        $campaignReportBaidu = new CampaignReportBaidu();
        $_report = $campaignReportBaidu->report($account['username'], $account['accessToken'], $startDate, $endDate);
        //dump($_report);
        //dump($campaignsMap);
        $this->_handleReportBaidu($account, $_report, $campaignsMap, $oldComputerReport, $oldMobileReport);
        
        return ['computer'=>$oldComputerReport,'mobile'=>$oldMobileReport];
    }
    
    /**
     * 处理从baidu推广计划维度接口获取的数据报表
     * @param array $account          帐户信息
     * @param array $reportGetFromApi 从baidu接口获取的报表数据
     * @param array $campaignsMap     推广计划映射
     * @param array $oldComputerReportFromDb
     *                                从数据库查找的推广计划报表数据(PC端)
     *                                该数据需要实时修改 所以使用引用传递
     * @param array $oldMobileReportFromDb
     *                                从数据库查找的推广计划报表数据(移动端)
     *                                该数据需要实时修改 所以使用引用传递
     *   接口返回数据示例
     *   ["date"] => string(10) "2025-06-05"
     *   ["userName"] => string(19) "CS-扳手科技-主"
     *   ["campaignNameStatus"] => string(19) "量U - 点击出价"
     *   ["campaignStatus"] => string(1) "0"
     *   ["campaignId"] => int(1017502108)
     *   ["impression"] => int(7126)
     *   ["click"] => int(134)
     *   ["cost"] => float(42.12)
     *   ["ctr"] => float(0.01880437833286556)      //点击率
     *   ["cpc"] => float(0.3143283582089552)       //平均点击价格
     *   ["device"] => string(12) "移动设备"
     *   ["ocpcConversionsDetail12"] => int(0)
     */
    private function _handleReportBaidu($account, $reportGetFromApi, $campaignsMap, &$oldComputerReportFromDb, &$oldMobileReportFromDb){
        //dump($reportGetFromApi);
        //dump($campaignsMap);
        //exit;
        if(count($reportGetFromApi['header']['failures']) > 0){
            log_message('info','_handleReportBaidu fail');
        } else {
            $report = isset($reportGetFromApi['body']['data'][0]['rows']) ? $reportGetFromApi['body']['data'][0]['rows'] : [];
            if(count($report) > 0){
                foreach ($report as $v){
                    if(!isset($campaignsMap[$v['campaignId']])){
                        //获取到该推广计划的报表数据 但是该推广计划的基础信息还未入库
                        //则记录错误日志
                        log_message('info','campaign error 100');
                        continue;
                    }
                    $insertReport = [
                        'account_id'=>$account['id'],
                        'campaign_id'=>$campaignsMap[$v['campaignId']]['id'],
                        'media_campaignid'=>$v['campaignId'],
                        'consume'=>intval($v['cost']*100),
                        'shows'=>$v['impression'],
                        'clicks'=>$v['click'],
                        'converts'=>0,              //百度的转化量需要根据传入的指标来获取
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
                    $decideCode = $this->insertOrUpdateOrNothing($insertReport, $oldReportFromDb, 'media_campaignid');
                    /* if($decide['code'] == 2){
                        $insertID = $this->insert($insertReport, true);
                        if($insertID){
                            $insertReport['id'] = $insertID;
                            $oldReportFromDb[$insertReport['media_campaignid']][$insertReport['sdate']] = $insertReport;
                        } else {
                            //TODO 写入失败 记录日志
                            continue;
                        }
                    } elseif($decide['code'] == 1){
                        $updateRes = $this->save($decide['update']);
                        if($updateRes > 0){
                            $insertReport['id'] = $decide['update']['id'];
                            $oldReportFromDb[$insertReport['media_campaignid']][$insertReport['sdate']] = $insertReport;
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
     * Bing 广告计划投放报表数据
     * @param array $accInfo
     * @param array $campaigns
     */
    public function getCampaignReportBing($account, $startDate, $endDate, $oldReports, $campaigns){
        $oldComputerReport = isset($oldReports[$account['id']]['computer']) ? $oldReports[$account['id']]['computer'] : [];
        $oldMobileReport = isset($oldReports[$account['id']]['mobile']) ? $oldReports[$account['id']]['mobile'] : [];
        $oldOthersReport = isset($oldReports[$account['id']]['others']) ? $oldReports[$account['id']]['others'] : [];
        $campaignsMap = array_column($campaigns, null, 'media_campaignid');
        
        //对日期进行校验
        //$timeStamps = $this->checkDate($startDate, $endDate);
        $campaignReportBing = new CampaignReportBing();
        $_report = $campaignReportBing->report($account['devkey'], $account['accessToken'], $account['media_account_id'], $startDate, $endDate);
        //dump($_report);
        //dump($campaignsMap);
        $this->_handleReportBing($account, $_report, $campaignsMap, $oldComputerReport, $oldMobileReport, $oldOthersReport);
        
        return ['computer'=>$oldComputerReport, 'mobile'=>$oldMobileReport, 'others'=>$oldOthersReport];
    }
    
   /*
    * 处理从 Bing 推广计划维度接口获取的数据报表
    * @param array $account          帐户信息
    * @param array $reportGetFromApi 从 Bing 接口获取的报表数据
    * @param array $campaignsMap     推广计划（以推广计划id作为key）
    * @param array $oldComputerReportFromDb
    *                                从数据库查找的推广计划报表数据(PC端)
    *                                该数据需要实时修改 所以使用引用传递
    * @param array $oldMobileReportFromDb
    *                                从数据库查找的推广计划报表数据(移动端)
    *                                该数据需要实时修改 所以使用引用传递
    * @param array $oldOthersReportFromDb
    *                                从数据库查找的推广计划报表数据(其他)
    *                                该数据需要实时修改 所以使用引用传递
    */
    private function _handleReportBing($account, $reportGetFromApi, $campaignsMap, &$oldComputerReportFromDb, &$oldMobileReportFromDb, &$oldOthersReportFromDb){
        if(count($reportGetFromApi) > 0){
            $_map = [
                'Computer'=>['deviceTypeID'=>2,'oldData'=>&$oldComputerReportFromDb],
                'Smartphone'=>['deviceTypeID'=>1,'oldData'=>&$oldMobileReportFromDb],
                'Tablet'=>['deviceTypeID'=>3,'oldData'=>&$oldOthersReportFromDb]
            ];
            foreach ($reportGetFromApi as $v){
                if(empty($account['balance'])){
                    //获取到该帐户的报表数据 但是该帐户的基础信息还未入库
                    //则记录错误日志
                    log_message('info','account error 100');
                    continue;
                }
                
                $insertReport = [
                    'account_id'=>$account['id'],
                    'campaign_id'=>$campaignsMap[$v['CampaignId']]['id'],
                    'media_campaignid'=>$v['CampaignId'],
                    'uid'=>$account['uid'],
                    'consume'=>intval($v['Spend']*100),
                    'shows'=>$v['Impressions'],
                    'clicks'=>$v['Clicks'],
                    'device'=>$_map[$v['DeviceType']]['deviceTypeID'],
                    'sdate'=>$v['TimePeriod']
                ];
                //-2写入失败 -1更新失败 0无动作 1更新成功 2写入成功
                $this->insertOrUpdateOrNothing($insertReport, $_map[$v['DeviceType']]['oldData'], 'media_campaignid');
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
        if(!isset($oldReport[$insertReport['media_campaignid']][$insertReport['sdate']])){
            //exit;
            return ['code'=>2,'update'=>[]];
        }
        
        $update = [];
        $compare = $oldReport[$insertReport['media_campaignid']][$insertReport['sdate']];
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