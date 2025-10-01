<?php
/**
 *与媒体渠道授权帐号相关的数据模型
 */
namespace App\Models;

use App\Libraries\MarketingApi360\Account as Account360;
use App\Libraries\MarketingApiBaidu\Account as AccountBaidu;
use App\Libraries\MarketingApiBing\Account as AccountBing;
use CodeIgniter\CLI\CLI;

class MediaAccountModel extends \CodeIgniter\Model
{
    protected $table = 'dev_media_account';
    protected $allowedFields = [
        'username',
        'balance',
        'budget',
        'company_name',
        'media_account_id',
        'status',
        'accessToken',
        'expire_time',
        'auth_time'
    ];
    
    /*protected $needUpdateFields = [
        'username',
        'balance',
        'budget',
        'company_name',
        'media_account_id',
        'status'
    ];*/
    
    /**
     * 根据查询条件查询所有的广告帐户
     * @param unknown $where
     * @param string $fields
     * @return unknown
     */
    /*public function getAccount($where, $fields = '*'){
        if(empty($where) || !is_array($where)){
            $where_str = "1=1";
            $values = [];
        } else {
            $temp = array_keys($where);
            $whereTemp = [];
            foreach ($temp as $key){
                $whereTemp[] = $key.'=?';
            }
            $where_str = implode(" AND ", $whereTemp);
            $values = array_values($where);
        }
        $sql = "SELECT ".$fields." FROM ".$this->table." WHERE ".$where_str;
        $result = $this->db->query($sql, $values)->getResultArray();
        //echo $this->db->getLastQuery();
        return $result;
    }*/
    
    /**
     * 根据查询条件查询所有的广告帐户
     * 以 account id 作为键名
     * @param unknown $where
     * @param string $fields
     * @return unknown
     */
    public function getAccountsMap($where = '1=1', $fields = '*'){
        $res = [];
        $allAccounts = $this->db->table('dev_media_account')->select($fields)->where($where)->get()->getResultArray();
        if(count($allAccounts) > 0){
            $res = array_column($allAccounts, null, 'id');
        }
        return $res;
    }
    
    /**
     * 360
     * 获取帐户余额 日预算等基本信息
     */
    public function getAccountBase360($account){
        do {
            //获取帐户基础数据
            $account360  = new Account360();
            $baseInfo = $account360->getInfo($account['devkey'], $account['accessToken']);
            if(isset($baseInfo['failures'])){
                break;
            }
            $update = ['username'=>$baseInfo['userName'],
                       'balance'=>intval($baseInfo['balance']*100),
                       'budget'=>intval($baseInfo['budget']*100),
                       'company_name'=>$baseInfo['companyName'],
                       'media_account_id'=>$baseInfo['uid'],
                       'status'=>$this->_360StatusMap(intval($baseInfo['status'])),
                       'media_status'=>intval($baseInfo['status'])
            ];
            //请求接口成功 判断是否需要更新数据库
            $account = $this->_updateOrNothing($update, $account);
        } while(false);
        
        return $account;
    }
    
    /**
     * 获取 baidu 投放帐户基础信息
     * @param unknown $account
     * @return number[]|unknown[]|NULL[]|mixed[]
     */
    public function getAccountBaseBaidu($account){
        do {
            //获取帐户基础数据
            $accountBaidu  = new AccountBaidu();
            $baseInfo = $accountBaidu->getInfo($account['username'], $account['accessToken']);
            //CLI::print(json_encode($baseInfo));
            if(count($baseInfo['header']['failures']) > 0){
                break;
            }
            $data = $baseInfo['body']['data'][0];
            $update = [
                'balance'=>intval($data['balance']*100),
                'budget'=>intval($data['budget']*100),
                'company_name'=>$data['liceName'],
                'media_account_id'=>$data['userId'],
                'status'=>$this->_baiduStatusMap(intval($data['userStat'])),
                'media_status'=>intval($data['userStat'])
            ];
            //请求接口成功 判断是否需要更新数据库
            $account = $this->_updateOrNothing($update, $account);
        } while(false);
        
        return $account;
    }
    
    /**
     * 获取 bing 投放帐户基础信息
     * @param unknown $account
     * @return number[]|unknown[]|NULL[]|mixed[]
     */
    public function getAccountBaseBing($account){
        do {
            $accountBing  = new AccountBing();
            $baseInfo = $accountBing->getInfo($account['devkey'], $account['accessToken'], $account['media_account_id']);
            if(isset($baseInfo['account']['Errors']) || isset($baseInfo['account']['OperationErrors']) || isset($baseInfo['balance']['Errors']) || isset($baseInfo['balance']['OperationErrors'])){
                break;
            }
            $_status = $baseInfo['account']['Account']['AccountLifeCycleStatus'];
            $update = [
                'username'=>$baseInfo['account']['Account']['Name'],
                'balance'=>intval($baseInfo['balance']['InsertionOrders'][0]['BudgetRemaining']*100),
                'budget'=>'-1',
                'company_name'=>$baseInfo['account']['Account']['BusinessAddress']['BusinessName'],
                'media_account_id'=>$baseInfo['account']['Account']['Id'],
                'status'=>$this->_bingStatusMap($_status),
                'media_status'=>$_status
            ];
            $account = $this->_updateOrNothing($update, $account);
        } while(false);
        
        return $account;
    }
    
    /**
     * 
     * @param unknown $newAccount
     * @param unknown $oldReport
     */
    private function _updateOrNothing($updateInfo, $oldAccount){
        $update = [];
        foreach ($updateInfo as $prop=>$value){
            if($value != $oldAccount[$prop]){
                $update[$prop] = $value;
                $oldAccount[$prop] = $value;
            }
        }
        if(count($update) > 0){
            $res = $this->update($oldAccount['id'], $update);
            //更新结果记录日志
        }
        return $oldAccount;
    }
    
    /**
     * 转换 360 投放帐户的status字段
     * 详细文档可见 https://open.e.360.cn/api/account_getInfo.html status字段说明
     * @param unknown $status
     * @return number
     */
    private function _360StatusMap($status){
        return $status == 1 ? 1 : 0;
    }
    
    /**
     * 转换 百度 投放帐户的status字段
     * 详细文档可见 https://dev2.baidu.com/content?sceneType=0&pageId=100256&nodeId=63 userStat字段说明
     * @param unknown $status
     * @return number
     */
    private function _baiduStatusMap($status){
        return $status == 2 ? 1 : 0;
    }
    
    /**
     * 转换 Bing 投放帐户的status字段
     * 详细文档可见 https://learn.microsoft.com/zh-cn/advertising/customer-management-service/advertiseraccount?view=bingads-13&tabs=json#accountlifecyclestatus   AccountLifeCycleStatus字段说明
     * @param unknown $status
     * @return number
     */
    private function _bingStatusMap($status){
        return $status == 'Active' ? 1 : 0;
    }
    
    /**
     * 删除某个投放帐户
     * @param unknown $where
     * @return unknown
     */
    public function delAccount($where){
        $res = $this->where($where)->delete();
        return $res->connID->affected_rows;
    }
    
    /**
     * 更新360帐户信息
     */
    public function updateAccount360($accInfo, $budget){
        $account360  = new Account360();
        $resArr = $account360->setDayBudget($accInfo['devkey'], $accInfo['accessToken'], $budget);
        //dump($resArr);
        //exit;
        if(isset($resArr['failures'])){
            //十分钟不能连续修改
            return ["code"=>198,"msg"=>$resArr['failures'][0]['message']];
        } else {
            if(intval($resArr['affectedRecords']) > 0){
                //修改成功写入数据库 单位为 分
                $this->update($accInfo['id'], ['budget'=>intval($budget*100)]);
                return ["code"=>200,"msg"=>"ok"];
            } else {
                return ["code"=>199,"msg"=>"fail"];
            }
        }
    }
    
    /**
     * 更新 baidu 帐户信息
     */
    public function updateAccountBaidu($accInfo, $budget){
        $accountBaidu  = new AccountBaidu();
        $resArr = $accountBaidu->setDayBudget($accInfo['username'], $accInfo['accessToken'], $budget);
        //dump($resArr);
        //exit;2
        if(count($resArr['header']['failures']) > 0){
            //5分钟内不能连续修改
            return ["code"=>198,"msg"=>$resArr['header']['failures'][0]['message']];
        } else {
            //修改成功写入数据库 单位为 分
            $this->update($accInfo['id'], ['budget'=>intval($budget*100)]);
            return ["code"=>200,"msg"=>"ok"];
        }
    }
    
    
}