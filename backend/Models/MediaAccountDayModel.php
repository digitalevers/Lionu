<?php
/**
 * 推广帐户投放数据日报表模型
 */
namespace App\Models;

//use App\Libraries\MarketingApi360\SEM\AdvManage\AdvCampaign as AdvCampaign360;
use App\Libraries\MarketingApi360\SEM\AdvReport\Account as AccountReport360;
use App\Libraries\MarketingApiBaidu\SEM\AdvReport\Account as AccountReportBaidu;
use App\Libraries\MarketingApiBing\SEM\AdvReport\Account as AccountReportBing;
use CodeIgniter\CLI\CLI;

class MediaAccountDayModel extends BaseModel
{
    protected $table = 'dev_report_account_day';
    
    protected $allowedFields = [
        'account_id',
        'media_accountid',
        'uid',
        'consume',
        'shows',
        'clicks',
        'converts',
        'device',
        'sdate'
    ];
    
    protected $needUpdateFields = [
        'consume',
        'shows',
        'clicks',
        'converts',
    ];
    
    /**
     * 插入或者更新帐户维度日报表数据
     * 如果没有记录则添加
     * 如果已有该记录且日期为今天 则更新记录(只更新今天的实时记录，之前的数据都是固定的结算数据，不需要修改)
     * @param array $data
     * @return mixed
     */
    /* public function addOrUpdateAccountDayReport(array $data)
    {
        //dump($data);
        //exit;
        $today = date('Y-m-d');
        $keys = array_keys($data);
        $values = array_values($data);
        $sql = "INSERT INTO dev_report_account_day (".implode(',', $keys).")
            VALUES (".implode(',', array_fill(0, count($values), '?')).")
            ON DUPLICATE KEY UPDATE ";
        $update_parts = [];
        foreach ($keys as $key) {
            //$update_parts[] = "$key = VALUES($key)";
            $update_parts[] = "$key = IF(sdate = '$today', VALUES($key), $key)";     //$key = VALUES($key)
        }
        $sql .= implode(', ', $update_parts);
        $result = $this->db->query($sql, $values);
        $affected_rows = $result->connID->affected_rows;        //-1、sql执行有误       0、没有插入或者更新记录     1、插入或更新一条记录
        //echo $this->db->getLastQuery();
        //dump($affected_rows);
        return $affected_rows;
    } */
    
    /**
     * 带事务的批量新增
     * TODO 批量一次性插入
     * @param array $datas 媒体接口返送的日报表数据
     * @param unknown $account  媒体帐号信息
     * @return boolean|unknown[][]
     */
    /* public function addOrUpdateAccountDayReports(array $datas, $account, $keyMap = [])
    {
        $result = [];
        $this->db->transStart();
        foreach ($datas as $data) {
            $_temp = ['account_id'=>$account['id']];
            
            foreach ($data as $k=>$v){
                if(isset($keyMap[$k])){
                    $_temp[$keyMap[$k]] = $v;
                }
            }
            $affected_rows = $this->addOrUpdateAccountDayReport($_temp);
            if ($affected_rows >= 0) {
                $result[] = $_temp;
            } else {
                $this->db->transRollback();
                return false;
            }
        }
        
        $this->db->transComplete();
        return $result;
    } */
    
    /**
     * 获取帐号日报表记录
     * @param unknown $account    帐号信息
     * @param unknown $startDate  查找起始日期
     * @param unknown $endDate    查找结束日期
     */
    /* public function getAccountDayReports($account, $startDate, $endDate){
        $result = $this->asArray()->where("`account_id`=".$account['id']." AND `sdate` >= '".$startDate."' AND `sdate` <= '".$endDate."'")->findAll();
        //echo $this->db->getLastQuery();
        return $result;
    } */
    
    /**
     * 获取数据库中的广告帐户报表数据
     * @param unknown $uid
     * @param unknown $startDate
     * @param unknown $endDate
     * @return array|unknown
     */
    public function getOldReports($uid, $startDate, $endDate){
        $res = [];
        $reports = $this->where("uid=".$uid." AND sdate>='".$startDate."' AND sdate<='".$endDate."'")->findAll();
        if(count($reports) > 0){
            foreach ($reports as $v){
                //$res[$v['account_id']][$v['sdate']] = $v;
                if($v['device'] == 1){
                    $res[$v['account_id']]['mobile'][$v['media_accountid']][$v['sdate']] = $v;
                } elseif($v['device'] == 2) {
                    $res[$v['account_id']]['computer'][$v['media_accountid']][$v['sdate']] = $v;
                } else {
                    $res[$v['account_id']]['others'][$v['media_accountid']][$v['sdate']] = $v;
                }
            }
        }
        return $res;
    }
    
    /**
     * 360
     * 1. 获取帐户维度投放 消耗 转化等数据
     * 2. 360无法直接从接口获取帐户维度的转化数 需要获取到所有推广计划的数据 然后累加
     */
    public function getAccountReport360($account, $startDate, $endDate, $oldReports){
        $oldComputerReport = isset($oldReports[$account['id']]['computer']) ? $oldReports[$account['id']]['computer'] : [];
        $oldMobileReport = isset($oldReports[$account['id']]['mobile']) ? $oldReports[$account['id']]['mobile'] : [];
        /* CLI::print('oldreports-'.json_encode($oldReports));
        CLI::print("\r\n");
        CLI::print('account-'.json_encode($account));
        CLI::print("\r\n");
        CLI::print(json_encode($oldComputerReport));
        CLI::print("\r\n");
        CLI::print(json_encode($oldMobileReport));
        exit; */
        
        $timeStamps = $this->checkDate($startDate, $endDate);
        $accountReport360 = new AccountReport360();
        //360 帐户维度的历史数据和实时数据为同一接口
        $_pcTodayReport = $accountReport360->report($account['devkey'], $account['accessToken'], $startDate, $endDate, 'computer');
        $this->_handleReport360($account, $_pcTodayReport, $oldComputerReport, 2);
        $_mobileTodayReport = $accountReport360->report($account['devkey'], $account['accessToken'], $startDate, $endDate, 'mobile');
        $this->_handleReport360($account, $_mobileTodayReport, $oldMobileReport, 1);
        
        return ['computer'=>$oldComputerReport,'mobile'=>$oldMobileReport];
    }
    

    
    /**
     * 处理从360帐户维度接口获取的数据报表
     * @param array $account          帐户信息
     * @param array $reportGetFromApi 从360接口获取的帐户报表数据
     * @param array $oldReportFromDb  从数据库查找的帐户报表数据
     *                                该数据需要实时修改 所以使用引用传递
     *
     * @param int   $device           设备      1 mobile 2 computer
     */
    private function _handleReport360($account, $reportGetFromApi, &$oldReportFromDb, $device){
        //dump($account);
        //exit;
        if(isset($reportGetFromApi['failures'])){
            log_message('info','_handleReport360 fail');
        } else {
            $report = $reportGetFromApi['dailyList'];
            if(count($report) > 0){
                foreach ($report as $v){
                    if(empty($account['media_account_id'])){
                        //获取到该帐户的报表数据 但是该帐户的基础信息还未入库
                        //则记录错误日志
                        log_message('info','account error 100');
                        continue;
                    }
     
                    $insertReport = [
                        'account_id'=>$account['id'],
                        'media_accountid'=>$account['media_account_id'],
                        'uid'=>$account['uid'],
                        'consume'=>intval($v['totalCost']*100),
                        'shows'=>$v['views'],
                        'clicks'=>$v['clicks'],
                        'device'=>$device,
                        'sdate'=>$v['date']
                    ];
                    //CLI::print(json_encode($insertReport));
                    //CLI::print("\r\n");
                    //CLI::print(json_encode($oldReportFromDb));
                    //-2写入失败 -1更新失败 0无动作 1更新成功 2写入成功
                    $decideRes = $this->insertOrUpdateOrNothing($insertReport, $oldReportFromDb, 'media_accountid');
                    if($decideRes < 0){
                        continue;
                    }
                }
            }
        }
    }
    
    
    /**
     * 百度帐户维度投放数据报表
     * @param unknown $account
     * @param unknown $startDate
     * @param unknown $endDate
     * @param unknown $oldReports
     */
    public function getAccountReportBaidu($account, $startDate, $endDate, $oldReports){
        $oldComputerReport = isset($oldReports[$account['id']]['computer']) ? $oldReports[$account['id']]['computer'] : [];
        $oldMobileReport = isset($oldReports[$account['id']]['mobile']) ? $oldReports[$account['id']]['mobile'] : [];

        //$timeStamps = $this->checkDate($startDate, $endDate);
        $accountReportBaidu = new AccountReportBaidu();
        $_pcTodayReport = $accountReportBaidu->report($account['username'], $account['accessToken'], $startDate, $endDate, 'computer');
        $this->_handleReportBaidu($account, $_pcTodayReport, $oldComputerReport, 2);
        $_mobileTodayReport = $accountReportBaidu->report($account['username'], $account['accessToken'], $startDate, $endDate, 'mobile');
        $this->_handleReportBaidu($account, $_mobileTodayReport, $oldMobileReport, 1);
        
        return ['computer'=>$oldComputerReport,'mobile'=>$oldMobileReport];
    }
    
    /**
     * 处理从baidu帐户维度接口获取的数据报表
     * @param array $account          帐户信息
     * @param array $reportGetFromApi 从baidu接口获取的帐户报表数据
     * @param array $oldReportFromDb  从数据库查找的帐户报表数据
     *                                该数据需要实时修改 所以使用引用传递
     *
     * @param int   $device           设备      1 mobile 2 computer
     */
    private function _handleReportBaidu($account, $reportGetFromApi, &$oldReportFromDb, $device){
        if(count($reportGetFromApi['header']['failures']) > 0){
            log_message('info','_handleReportBaidu fail');
        } else {
            $report = isset($reportGetFromApi['body']['data'][0]['rows']) ? $reportGetFromApi['body']['data'][0]['rows'] : [];
            if(count($report) > 0){
                foreach ($report as $v){
                    if(empty($account['balance'])){
                        //获取到该帐户的报表数据 但是该帐户的基础信息还未入库
                        //则记录错误日志
                        log_message('info','account error 100');
                        continue;
                    }
                    
                    $insertReport = [
                        'account_id'=>$account['id'],
                        'media_accountid'=>$account['media_account_id'],
                        'uid'=>$account['uid'],
                        'consume'=>intval($v['cost']*100),
                        'shows'=>$v['impression'],
                        'clicks'=>$v['click'],
                        'device'=>$device,
                        'sdate'=>$v['date']
                    ];
                    //-2写入失败 -1更新失败 0无动作 1更新成功 2写入成功
                    $decideRes = $this->insertOrUpdateOrNothing($insertReport, $oldReportFromDb, 'media_accountid');
                    if($decideRes < 0){
                        continue;
                    }
                }
            }
        }
    }
    
    /**
     * bing 帐户维度投放数据报表
     * @param unknown $account
     * @param unknown $startDate
     * @param unknown $endDate
     * @param unknown $oldReports
     */
    public function getAccountReportBing($account, $startDate, $endDate, $oldReports){
        $oldComputerReport = isset($oldReports[$account['id']]['computer']) ? $oldReports[$account['id']]['computer'] : [];
        $oldMobileReport = isset($oldReports[$account['id']]['mobile']) ? $oldReports[$account['id']]['mobile'] : [];
        $oldOthersReport = isset($oldReports[$account['id']]['others']) ? $oldReports[$account['id']]['others'] : [];
        
        //$timeStamps = $this->checkDate($startDate, $endDate);
        $accountReportBing = new AccountReportBing();
        $latestReport = $accountReportBing->report($account['devkey'], $account['accessToken'], $account['media_account_id'], $startDate, $endDate);
        $this->_handleReportBing($account, $latestReport, $oldComputerReport, $oldMobileReport, $oldOthersReport);
        
        return ['computer'=>$oldComputerReport, 'mobile'=>$oldMobileReport, 'others'=>$oldOthersReport];
    }

    /**
     * 处理从 bing 帐户维度接口获取的投放报表数据
     * @param unknown $account              帐户信息
     * @param unknown $reportGetFromApi     从 bing 接口获取的帐户报表数据
     * @param unknown $oldComputerReport    从数据库查找的帐户报表数据(PC端)
     * @param unknown $oldMobileReport      从数据库查找的帐户报表数据(移动端)
     * @param unknown $oldOthersReport      从数据库查找的帐户报表数据(其他)
     */
    private function _handleReportBing($account, $reportGetFromApi, &$oldComputerReport, &$oldMobileReport, &$oldOthersReport){
        if(count($reportGetFromApi) > 0){
            $_map = [
                'Computer'=>['deviceTypeID'=>2,'oldData'=>&$oldComputerReport],
                'Smartphone'=>['deviceTypeID'=>1,'oldData'=>&$oldMobileReport],
                'Tablet'=>['deviceTypeID'=>3,'oldData'=>&$oldOthersReport]
            ];
            //dump($reportGetFromApi);
            //dump($oldComputerReport);
            //dump($oldMobileReport);
            //dump($oldOthersReport);
            //exit;
            foreach ($reportGetFromApi as $v){
                if(empty($account['balance'])){
                    //获取到该帐户的报表数据 但是该帐户的基础信息还未入库
                    //则记录错误日志
                    log_message('info','account error 100');
                    continue;
                }
                
                $insertReport = [
                    'account_id'=>$account['id'],
                    'media_accountid'=>$account['media_account_id'],
                    'uid'=>$account['uid'],
                    'consume'=>intval($v['Spend']*100),
                    'shows'=>$v['Impressions'],
                    'clicks'=>$v['Clicks'],
                    'device'=>$_map[$v['DeviceType']]['deviceTypeID'],
                    'sdate'=>$v['TimePeriod']
                ];
                //-2写入失败 -1更新失败 0无动作 1更新成功 2写入成功
                $decideRes = $this->insertOrUpdateOrNothing($insertReport, $_map[$v['DeviceType']]['oldData'], 'media_accountid');
                /* if($decideRes < 0){
                    continue;
                } */
            }
        }
    }
    
}