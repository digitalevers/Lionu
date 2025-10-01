<?php
/**
 * CodeIgniter 4 使用递归方式扫描 Commands 目录，会遍历所有层级的子目录
 * 只要文件路径和命名空间匹配，便会自动发现并执行该文件
 */
namespace App\Commands\CronJobBing\SEM;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class refreshToken extends BaseCommand
{
    protected $group = 'Tasks';
    protected $name = 'bing:refreshToken';
    protected $description = 'Run scheduled cron tasks';
    
    /**
     * 刷新所有 Bing 帐户的accessToken
     * {@inheritDoc}
     * @see \CodeIgniter\CLI\BaseCommand::run()
     */
    public function run(array $params)
    {
        // 默认执行帮助信息
        //$this->showHelp();
        $accountModel = model('MediaAccountModel');
        $accountInfo = $accountModel->getAccountsMap(['channel_ename'=>'bing'],'`id`, `app_id`, `devsecret`, `refreshToken`');
        if(count($accountInfo) > 0){
            foreach ($accountInfo as $info){
                $api = 'https://login.microsoftonline.com/common/oauth2/v2.0/token';
                $bodyArr = [
                    'client_id'=>$info['app_id'],
                    'scope'=>'https://ads.microsoft.com/msads.manage',
                    'refresh_token'=>$info['refreshToken'],
                    'grant_type'=>'refresh_token',
                    'client_secret'=>$info['devsecret'],
                ];
                $body = http_build_query($bodyArr);
                $header = ['Content-Type: application/x-www-form-urlencoded;charset=utf-8'];
                $res = requestPost($api, $body, $header);
                //记录日志
                file_put_contents(WRITEPATH.'/logs/semRefreshTokenBing.log', $body.'---'.$res."\r\n", FILE_APPEND);
                $resArr = json_decode($res, true);
                if(!isset($resArr['error'])){
                    //请求成功则更新数据库accessToken
                    $now = time();
                    $update = [
                        'id'=>$info['id'],
                        'accessToken'=>$resArr['access_token'],
                        'expire_time'=>date('Y-m-d H:i:s',$now + $resArr['expires_in']),
                        'auth_time'=>date('Y-m-d H:i:s',$now)
                    ];
                    $accountModel->save($update);
                }
            }
        }
    }
    
    /* public function showHelp()
     {
     CLI::write('Available methods:');
     CLI::write('  stats    - Generate daily statistics');
     CLI::write('  clean    - Cleanup temporary files');
     CLI::write('  notify   - Send notifications (add type parameter)');
     } */
}