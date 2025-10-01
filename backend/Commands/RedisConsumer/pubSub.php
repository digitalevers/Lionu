<?php
/**
 * Redis发布订阅模式
 * 该脚本常驻内存
 * 用于实时接收redis的订阅信息-并从marketing api获取最新的广告数据和报表
 * @author Administrator
 *
 */
namespace App\Commands\RedisConsumer;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\Tools\RedisTool as RedisTool;

class pubSub extends BaseCommand
{
    protected $group = 'Tasks';
    protected $name = 'pubSub';
    protected $description = 'Run scheduled cron tasks';
    //private $running = true;
    
    /**
     * 从队列中取出帐户信息 并从marketing api同步数据
     * {@inheritDoc}
     * @see \CodeIgniter\CLI\BaseCommand::run()
     */
    public function run(array $params)
    {
        ini_set('default_socket_timeout', -1);
        // 检查参数
        if (count($params) < 3) {
            CLI::error('Usage: php spark pubSub <model_name> <method_name> <channel>');
            return;
        }
        $modelName = $params[0];
        $methodName = $params[1];
        $channelName = $params[2];
        // 加载模型
        try {
            $model = model($modelName);
            if (!$model) {
                CLI::error("Model '{$modelName}' not found.");
                return;
            }
        } catch (\Exception $e) {
            CLI::error("Failed to load model '{$modelName}': " . $e->getMessage());
            return;
        }
        // 检查方法是否存在
        if (!method_exists($model, $methodName)) {
            CLI::error("Method '{$methodName}' not found in model '{$modelName}'.");
            return;
        }
        //
        $tool = new RedisTool($channelName);
        //第一种传递回调函数的方式
        /* $callback = function($redis, $channel, $message) use ($model, $methodName) {
            call_user_func([$model, $methodName], $redis, $channel, $message);
        }; */
        //第二种传递回调函数的方式
        $callback = [$model, $methodName];
        $tool->sub($callback);
    }
    
    /**
     * 注册信号处理器
     */
    /* private function registerSignalHandlers() {
        if (!function_exists('pcntl_signal')) {
            CLI::write("PCNTL扩展不可用，无法处理信号", 'yellow');
            return;
        }
        // 处理 SIGTERM 信号（优雅停止）
        pcntl_signal(SIGTERM, [$this, 'handleSignal']);
        // 处理 SIGINT 信号（Ctrl+C）
        pcntl_signal(SIGINT, [$this, 'handleSignal']);
        // 处理 SIGHUP 信号
        pcntl_signal(SIGHUP, [$this, 'handleSignal']);
    } */
    
    /**
     * 信号处理函数
     */
    /* public function handleSignal($signal) {
        $signalNames = [
            SIGTERM => 'SIGTERM',
            SIGINT => 'SIGINT',
            SIGTSTP => 'SIGTSTP'
        ];
        
        $signalName = $signalNames[$signal] ?? "SIGNAL:$signal";
        CLI::write("接收到信号: $signalName, 正在关闭...", 'yellow');
        
        // 设置运行状态为false
        $this->running = false;
        
        // 对于Ctrl+Z，直接退出
        if ($signal === SIGTSTP) {
            exit(0);
        }
    } */

}