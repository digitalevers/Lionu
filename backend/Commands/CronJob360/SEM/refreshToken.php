<?php
/**
 * CodeIgniter 4 使用递归方式扫描 Commands 目录，会遍历所有层级的子目录
 * 只要文件路径和命名空间匹配，便会自动发现并执行该文件
 */
namespace App\Commands\CronJob360\SEM;

use App\Libraries\MarketingApi360\Account as Account360;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class refreshToken extends BaseCommand
{
    protected $group = 'Tasks';
    protected $name = '360:refreshToken';
    protected $description = 'Run scheduled cron tasks';
    
    /**
     * 刷新所有 360 帐户的accessToken
     * {@inheritDoc}
     * @see \CodeIgniter\CLI\BaseCommand::run()
     */
    public function run(array $params)
    {
        $accountModel = model('MediaAccountModel');
        $accountInfo = $accountModel->getAccountsMap(['channel_ename'=>'s360'],'`id`, `app_id`, `devkey`, `devsecret`, `username`, `password`');
        if(count($accountInfo) > 0){
            $db = \Config\Database::connect();
            $mkApi360  = new Account360();
            foreach ($accountInfo as $info){
                $resArr = $mkApi360->clientLogin($info['devkey'], $info['devsecret'], $info['username'], $info['password']);
                if(isset($resArr['accessToken']) && !empty($resArr['accessToken'])){
                    //更新授权记录
                    $now = time();
                    $auth_time  = date('Y-m-d H:i:s', $now);
                    $expire_time = date('Y-m-d H:i:s', $now + 10 * 3600);   //token 十小时后过期
                    $sql = "UPDATE `dev_media_account` SET accessToken=?, expire_time=?, auth_time=? WHERE id=?";
                    $values = [$resArr['accessToken'], $expire_time, $auth_time, $info['id']];
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
        }
    }
}