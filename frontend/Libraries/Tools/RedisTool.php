<?php
namespace App\Libraries\Tools;

defined('FCPATH') OR exit('No direct script access allowed');

use CodeIgniter\CLI\CLI;

class RedisTool {
    private $redis;
    private $queueName;
    private $channelName;
    
    public function __construct($conName = 'ad_accounts') {
        $this->redis = new \Redis();
        $this->redis->pconnect('127.0.0.1', 6379);
        $this->queueName = $conName;
        $this->channelName = $conName;
    }
    
    /**
     * 将任务推入队列
     */
    public function push($jobData) {
        return $this->redis->lpush($this->queueName, json_encode($jobData));
    }
    
    /**
     * 从队列中获取任务
     */
    public function pop() {
        $data = $this->redis->brpop($this->queueName, 1);
        if ($data) {
            return json_decode($data[1], true);
        }
        return null;
    }
    
    /**
     * 获取队列长度
     */
    public function length() {
        return $this->redis->llen($this->queueName);
    }
    

    /**
     * 发布订阅模式
     * 发布消息
     * @param unknown $channelName  发布频道
     * @param unknown $content      发布消息内容
     */
    public function pub($content){
        $this->redis->publish($this->channelName, json_encode($content));
    }
    
    /**
     * 发布订阅模式
     * 订阅消息
     * @param unknown $callback 回调函数
     * 注意 var_dump print这样的传统方法可能无法打印输出
     * 必须使用 CLI::print进行打印 
     */
    public function sub($callback){
        $this->redis->setOption(\Redis::OPT_READ_TIMEOUT, -1);
        $this->redis->subscribe([$this->channelName], $callback);
        /* $this->redis->subscribe([$this->channelName], function($redis, $channel, $accMessage){
            //$acc = json_decode($accMessage, true);
            CLI::print($accMessage);
        }); */
    }
    
}

