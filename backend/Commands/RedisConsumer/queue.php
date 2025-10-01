<?php
/**
 * Redis队列模式
 * 该脚本常驻内存
 * 用于实时消费redis队列的帐号信息-并从marketing api获取最新的广告数据和报表
 * @author Administrator
 *
 */
namespace App\Commands\RedisConsumer;

use CodeIgniter\CLI\BaseCommand;
use App\Libraries\Tools\RedisQueue;

class queue extends BaseCommand {
    
    private $redis;
    private $queueName;
    private $running = true;
    private $pidFile;
    
    protected $group = 'Tasks';
    protected $name = 'queue';
    protected $description = 'Run scheduled cron tasks';
    
    public function __construct() {
        // 设置内存限制
        ini_set('memory_limit', '32M');
        set_time_limit(0);
        // 初始化 Redis 连接
        $this->redis = new \Redis();
        $this->redis->connect('127.0.0.1', 6379);
        // 注册信号处理器
        $this->registerSignalHandlers();
    }
    
    /**
     * 注册信号处理器
     */
    private function registerSignalHandlers() {
        // 处理 SIGTERM 信号（优雅停止）
        pcntl_signal(SIGTERM, [$this, 'handleSignal']);
        // 处理 SIGINT 信号（Ctrl+C）
        pcntl_signal(SIGINT, [$this, 'handleSignal']);
        // 处理 SIGHUP 信号
        pcntl_signal(SIGHUP, [$this, 'handleSignal']);
    }
    
    /**
     * 信号处理函数
     */
    public function handleSignal($signal) {
        $this->log("Received signal: $signal, shutting down gracefully...");
        $this->running = false;
    }
    
    /**
     * 写入 PID 文件
     */
    private function writePidFile() {
        file_put_contents($this->pidFile, getmypid());
    }
    
    /**
     * 删除 PID 文件
     */
    private function removePidFile() {
        if (file_exists($this->pidFile)) {
            unlink($this->pidFile);
        }
    }
    
    /**
     * 从队列中取出帐户信息 并从marketing api同步数据
     * {@inheritDoc}
     * @see \CodeIgniter\CLI\BaseCommand::run()
     */
    public function run(array $params) {
        $this->queueName = isset($params[0]) ? $params[0] : 'ad_accounts';
        //$this->pidFile = __DIR__ . '/worker.pid';
        // 写入 PID 文件
        //$this->writePidFile();
        //$this->log("Worker started, PID: " . getmypid());
        $this->log("Listening to queue: " . $this->queueName);
        
        while ($this->running) {
            // 处理信号
            pcntl_signal_dispatch();
            try {
                // 从 Redis 队列中获取任务
                $jobData = $this->popJob();   
                if ($jobData) {
                    $this->processJob($jobData);
                } else {
                    // 没有任务时短暂休眠
                    usleep(500000); // 0.5 秒
                }
            } catch (\Exception $e) {
                $this->log("Error processing job: " . $e->getMessage());
                // 避免频繁错误导致 CPU 占用过高
                sleep(1);
            }
        }
        $this->shutdown();
    }
    
    /**
     * 从队列中获取任务
     */
    private function popJob() {
        $data = $this->redis->brpop($this->queueName, 1);
        if ($data) {
            return json_decode($data[1], true);
        }
        return null;
    }
    
    /**
     * 处理任务
     */
    private function processJob($jobData) {
        $startTime = microtime(true);
        $jobId = $jobData['account_id'] ?? 'unknown';
        $this->log("Processing job: $jobId");
        
        try {
            // 根据任务类型执行相应操作
            switch ($jobData['job']) {
                case 'collect_ad_account_info':
                    $this->collectAdAccountInfo($jobData['account_id']);
                    break;
                default:
                    $this->log("Unknown job type: " . $jobData['job']);
            }
            
            $endTime = microtime(true);
            $duration = round(($endTime - $startTime) * 1000, 2);
            $this->log("Job $jobId completed in {$duration}ms");
        } catch (\Exception $e) {
            $this->log("Job $jobId failed: " . $e->getMessage());
            // 可以将失败任务推入失败队列
            $this->handleFailedJob($jobData, $e);
        }
    }
    
    /**
     * 采集广告账户信息
     */
    private function collectAdAccountInfo($accountId) {
        // 这里实现实际的采集逻辑
        $this->log("Collecting info for account: $accountId");
        // 模拟处理时间
        sleep(2);
        // 实际业务逻辑...
        // $collector = new AdAccountCollector($accountId);
        // $collector->collect();
    }
    
    /**
     * 处理失败任务
     */
    private function handleFailedJob($jobData, $exception) {
        $failedQueue = $this->queueName . ':failed';
        $jobData['failed_at'] = time();
        $jobData['error'] = $exception->getMessage();
        
        $this->redis->lpush($failedQueue, json_encode($jobData));
    }
    
    /**
     * 记录日志
     */
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        echo "[{$timestamp}] {$message}\n"; 
        // 同时写入日志文件
        file_put_contents(__DIR__ . '/worker.log', "[{$timestamp}] {$message}\n", FILE_APPEND);
    }
    
    /**
     * 优雅关闭
     */
    private function shutdown() {
        $this->log("Worker shutting down...");
        //$this->removePidFile();
        exit(0);
    }
}

