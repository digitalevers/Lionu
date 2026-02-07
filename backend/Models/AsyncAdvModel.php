<?php
/**
 * 作为订阅Redis的回调函数
 * 请求marketing api获取广告的基础数据和报表数据
 */
namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\CLI\CLI;

class AsyncAdvModel extends Model {

    /**
     * 处理订阅消息的回调方法
     * 控制台不能使用 var_dump 打印
     * 必须使用 CLI::print 系列函数打印
     */
    public function handleMessage($redis, $channel, $accMessage) {
        try {
            //查找end日期取当天
            $endDate = date('Y-m-d',time());
            $accountModel = model('MediaAccountModel');
            $accountReportModel = model('MediaAccountDayModel');
    
            $campaignModel = model('MediaCampaignModel');
            $campaignReportModel = model('MediaCampaignDayModel');
            
            $advGroupModel = model('MediaAdvGroupModel');
            $advGroupReportModel = model('MediaAdvGroupDayModel');
            
            $creativeModel = model('MediaCreativeModel');
            $creativeReportModel = model('MediaCreativeDayModel');
            //从 accMessage 中解析帐号信息
            $acc = json_decode($accMessage, true);
            switch ($acc['channel_ename']){
                case 's360':
                    $startDate = date('Y-m-d',time() - 90 * 24 *3600);//360只能查最近90天
                    //请求marketing api获取获取帐号基础数据和报表数据
                    $acc = $accountModel->getAccountBase360($acc);
                    //CLI::print(json_encode($acc));
                    $oldAccountReports = $accountReportModel->getOldReports($acc['uid'], $startDate, $endDate);
                    //CLI::print(json_encode($oldAccountReports));
                    $accountReportModel->getAccountReport360($acc, $startDate, $endDate, $oldAccountReports);
                    
                    //请求marketing api获取获取广告活动基础数据和报表数据
                    $oldCampaigns = $campaignModel->getCampaigns([$acc['id']=>$acc]);
                    $oldCampaigns[$acc['id']] = $campaignModel->getCampaignBase360($acc, $oldCampaigns);
                    $oldCampaignReports = $campaignReportModel->getOldReports([$acc['id']=>$acc], $startDate, $endDate);
                    $campaignReportModel->getCampaignReport360($acc, $startDate, $endDate, $oldCampaignReports, $oldCampaigns[$acc['id']]);
                    
                    //请求marketing api获取获取广告组基础数据和报表数据
                    $oldAdvGroups = $advGroupModel->getAdvGroups([$acc['id']=>$acc]);
                    $acc['campaigns'] = isset($oldCampaigns[$acc['id']]) ? $oldCampaigns[$acc['id']] : [];  //在帐户数据上挂载推广计划信息
                    $oldAdvGroups[$acc['id']] = $advGroupModel->getAdvGroupBase360($acc, $oldAdvGroups);
                    $oldAdvGroupReports = $advGroupReportModel->getOldReports([$acc['id']=>$acc], $startDate, $endDate);
                    $advGroupReportModel->getGroupReport360($acc, $startDate, $endDate, $oldAdvGroupReports, $oldAdvGroups[$acc['id']]);
                    
                    //请求marketing api获取获取创意基础数据和报表数据
                    $oldCreatives = $creativeModel->getCreatives([$acc['id']=>$acc]);
                    $acc['advgroups'] = isset($oldAdvGroups[$acc['id']]) ? $oldAdvGroups[$acc['id']] : [];  //在帐户数据上挂载广告组信息
                    $oldCreatives[$acc['id']] = $creativeModel->getCreativeBase360($acc, $oldCreatives);
                    $oldCreativeReports = $creativeReportModel->getOldReports([$acc['id']=>$acc], $startDate, $endDate);
                    $creativeReportModel->getCreativeReport360($acc, $startDate, $endDate, $oldCreativeReports, $oldCreatives[$acc['id']]);
                    break;
                case 'baidu':
                    $startDate = date('Y-m-d',time() - 730 * 24 *3600);//baidu能查最近 730天
                    //请求marketing api获取获取帐号基础数据和报表数据
                    $acc = $accountModel->getAccountBaseBaidu($acc);
                    $oldAccountReports = $accountReportModel->getOldReports($acc['uid'], $startDate, $endDate);
                    $accountReportModel->getAccountReportBaidu($acc, $startDate, $endDate, $oldAccountReports);
                    
                    //请求marketing api获取获取广告活动基础数据和报表数据
                    $oldCampaigns = $campaignModel->getCampaigns([$acc['id']=>$acc]);
                    $oldCampaigns[$acc['id']] = $campaignModel->getCampaignBaseBaidu($acc, $oldCampaigns);
                    $oldCampaignReports = $campaignReportModel->getOldReports([$acc['id']=>$acc], $startDate, $endDate);
                    $campaignReportModel->getCampaignReportBaidu($acc, $startDate, $endDate, $oldCampaignReports, $oldCampaigns[$acc['id']]);
                    
                    //请求marketing api获取获取广告组基础数据和报表数据
                    $oldAdvGroups = $advGroupModel->getAdvGroups([$acc['id']=>$acc]);
                    $acc['campaigns'] = isset($oldCampaigns[$acc['id']]) ? $oldCampaigns[$acc['id']] : [];  //在帐户数据上挂载推广计划信息
                    $oldAdvGroups[$acc['id']] = $advGroupModel->getAdvGroupBaseBaidu($acc, $oldAdvGroups);
                    $oldAdvGroupReports = $advGroupReportModel->getOldReports([$acc['id']=>$acc], $startDate, $endDate);
                    $advGroupReportModel->getGroupReportBaidu($acc, $startDate, $endDate, $oldAdvGroupReports, $oldAdvGroups[$acc['id']]);
                    
                    //请求marketing api获取获取创意基础数据和报表数据
                    $oldCreatives = $creativeModel->getCreatives([$acc['id']=>$acc]);
                    $acc['advgroups'] = isset($oldAdvGroups[$acc['id']]) ? $oldAdvGroups[$acc['id']] : [];  //在帐户数据上挂载广告组信息
                    $oldCreatives[$acc['id']] = $creativeModel->getCreativeBaseBaidu($acc, $oldCreatives);
                    $oldCreativeReports = $creativeReportModel->getOldReports([$acc['id']=>$acc], $startDate, $endDate);
                    $creativeReportModel->getCreativeReportBaidu($acc, $startDate, $endDate, $oldCreativeReports, $oldCreatives[$acc['id']]);
                    break;
                case 'bing':
                    $startDate = date('Y-m-d',time() - 365 * 24 *3600);//bing 没有限定查询最早时间 暂定为 365天
                    //请求marketing api获取获取帐号基础数据和报表数据
                    $acc = $accountModel->getAccountBaseBing($acc);
                    $oldAccountReports = $accountReportModel->getOldReports($acc['uid'], $startDate, $endDate); 
                    $accountReportModel->getAccountReportBing($acc, $startDate, $endDate, $oldAccountReports);
                    
                    //请求marketing api获取获取广告活动基础数据和报表数据
                    $oldCampaigns = $campaignModel->getCampaigns([$acc['id']=>$acc]);
                    $oldCampaigns[$acc['id']] = $campaignModel->getCampaignBaseBing($acc, $oldCampaigns);
                    $oldCampaignReports = $campaignReportModel->getOldReports([$acc['id']=>$acc], $startDate, $endDate);
                    $campaignReportModel->getCampaignReportBing($acc, $startDate, $endDate, $oldCampaignReports, $oldCampaigns[$acc['id']]);
                    
                    //请求marketing api获取获取广告组基础数据和报表数据
                    $oldAdvGroups = $advGroupModel->getAdvGroups([$acc['id']=>$acc]);
                    $acc['campaigns'] = isset($oldCampaigns[$acc['id']]) ? $oldCampaigns[$acc['id']] : [];  //在帐户数据上挂载推广计划信息
                    $oldAdvGroups[$acc['id']] = $advGroupModel->getAdvGroupBaseBing($acc, $oldAdvGroups);
                    $oldAdvGroupReports = $advGroupReportModel->getOldReports([$acc['id']=>$acc], $startDate, $endDate);
                    $advGroupReportModel->getGroupReportBing($acc, $startDate, $endDate, $oldAdvGroupReports, $oldAdvGroups[$acc['id']]);
                    
                    //请求marketing api获取获取创意基础数据和报表数据
                    $oldCreatives = $creativeModel->getCreatives([$acc['id']=>$acc]);
                    $acc['advgroups'] = isset($oldAdvGroups[$acc['id']]) ? $oldAdvGroups[$acc['id']] : [];  //在帐户数据上挂载广告组信息
                    $oldCreatives[$acc['id']] = $creativeModel->getCreativeBaseBing($acc, $oldCreatives);
                    $oldCreativeReports = $creativeReportModel->getOldReports([$acc['id']=>$acc], $startDate, $endDate);
                    $creativeReportModel->getCreativeReportBing($acc, $startDate, $endDate, $oldCreativeReports, $oldCreatives[$acc['id']]);
                    break;
                case 'tencent':
                    $startDate = date('Y-m-d',time() - 365 * 24 *3600); //Tencent支持查询最近一年(365天)内的数据
                    //请求marketing api获取获取帐号基础数据和报表数据
                    $acc = $accountModel->getAccountBaseTencent($acc);
                    CLI::print(json_encode($acc));
                    
                    break;
                default:
                    break;
            }
        } catch (\Exception $e){
            CLI::print($e->getMessage());
        }
    }
    
    
}