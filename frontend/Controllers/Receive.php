<?php 
namespace App\Controllers;

//use App\Models\NewsModel;

use Kafka\ProducerConfig;
use Kafka\Producer;

class Receive extends BaseController
{
    	
    //receive adv data
	/* public function clickhouse()
	{
	    $get = $this->request->getGet();
	    $data = array(
	        'ip'=>$get['ip'],
	        'plan_id'=>$get['plan_id'],
	        'channel_id'=>$get['channel_id'],
	        'mac_md5'=>$get['mac'],
	        'androidid_md5'=>$get['androidid'],
	        'imei_md5'=>$get['imei'],
	        'oaid'=>$get['oaid'],
	        'appid'=>$get['appid'],
	        'os'=>$get['os'],
	        'ts'=>$get['ts']
	    );
	    //print_r($post);
	    //exit();
	    $config = [
	        "host" => "localhost",
	        "port" => "9000",
	        "compression" => true
	    ];
	    $client = new \SeasClick($config);
	    //$client->execute("DROP TABLE test.array_test");
	    //$client->execute("CREATE DATABASE IF NOT EXISTS test");
	    //$client->execute("CREATE TABLE IF NOT EXISTS test.array_test (ip String, plan_id UInt32, channel_id UInt16,mac_md5 String,androidid_md5 String,imei_md5 String,oaid String,appid UInt16,os UInt8,ts UInt64) ENGINE = Memory");
	    $field = ['ip','plan_id','channel_id','mac_md5','androidid_md5','imei_md5','oaid','appid','os','ts'];
	    //print_r($field);
	    //print_r($data);
	    $client->insert("test.datadvs_android_click_log", $field, [array_values($data)]);
	    echo 'ok';
	} */

	/**
	 * 批量写入广告数据到clickhouse
	 */
	/* public function clickhousePost(){
	    $post = $this->request->getPost();
	    //var_dump($post['info']);
	    if(!empty($post['info'])){
	        $info = json_decode($post['info'],true);
	        if(is_array($info) && count($info) > 0){
	            $res = [];
	            foreach ($info as $v){
	                $data = array(
	                    'ip'=>$v['ip'],
	                    'plan_id'=>$v['plan_id'],
	                    'channel_id'=>$v['channel_id'],
	                    'mac_md5'=>$v['mac'],
	                    'androidid_md5'=>$v['androidid'],
	                    'imei_md5'=>$v['imei'],
	                    'oaid'=>$v['oaid'],
	                    'appid'=>$v['appid'],
	                    'os'=>$v['os'],
	                    'ts'=>$v['ts']
	                );
	                $data = array_values($data);
	                $res[] = $data;
	            }
	            
	            //print_r($res);
	            //exit();
	            $config = [
	                "host" => "localhost",
	                "port" => "9000",
	                "compression" => true
	            ];
	            $client = new \SeasClick($config);
	            //$client->execute("DROP TABLE test.array_test");
	            //$client->execute("CREATE DATABASE IF NOT EXISTS test");
	            //$client->execute("CREATE TABLE IF NOT EXISTS test.array_test (ip String, plan_id UInt32, channel_id UInt16,mac_md5 String,androidid_md5 String,imei_md5 String,oaid String,appid UInt16,os UInt8,ts UInt64) ENGINE = MergeTree() ORDER BY imei_md5");
	            $field = ['ip','plan_id','channel_id','mac_md5','androidid_md5','imei_md5','oaid','appid','os','ts'];
	            //print_r($field);
	            //print_r($data);
	            $client->insert("test.array_test", $field, $res);
	            echo 'ok';
	        } else {
	            exit('paramers error');
	        }
	    } else {
	        exit('paramers empty');
	    }
	} */
    
    /**
     * 接收媒体广告点击数据-Android
     */
    public function androidClick(){
        $get = $this->request->getGet(null, FILTER_SANITIZE_MAGIC_QUOTES);
        $clickData = array(
            'ip'=>$get['ip'],
            'plan_id'=>$get['plan_id'],
            'channel_id'=>$get['channel_id'],
            'mac_md5'=>$get['mac'],
            'androidid_md5'=>$get['androidid'],
            'imei_md5'=>$get['imei'],
            'oaid'=>$get['oaid'],
            'appid'=>$get['app_id'],
            'click_time'=>$get['ts']
        );
        $db = \Config\Database::connect();
        //$db->setDatabase('test');
        $res = $this->insert($db, 'log_android_click_data', $clickData);
        if(!$res){
             //TODO写错误日志并推送消息到前端
        }
    }
    
    /**
     * 接收媒体广告点击数据-iOS
     */
    public function iOSClick(){
        $get = $this->request->getGet(null, FILTER_SANITIZE_MAGIC_QUOTES);
        $this->_iOSClickParamsFilter($get);
        $clickData = $this->_iOSClickParamsMediaHandle($get);
        
        $db = \Config\Database::connect();
        //$db->setDatabase('test');
        $res = $this->insert($db, 'log_ios_click_data', $clickData);
        if(!$res){
            //TODO写错误日志并推送消息到前端
        } else {
            _json(['code'=>200,'msg'=>'ok'],1);
        }
    }
    
    /**
     * iOS点击数据过滤
     * @param array $clickData
     */
    private function _iOSClickParamsFilter($clickData){
        $required = array('idfa_md5','mac_md5','ip','appid','channel_id','plan_id');
        foreach ($required as $field){
            if(!isset($clickData[$field]) || empty($clickData[$field]) || (strpos($clickData[$field], '_') !== false)){
                //TODO 参数写入日志
                _json(['code'=>199,'msg'=>'iOS点击数据缺少参数'],1);
            }
        }
    }
    
    /**
     * iOS点击数据根据媒体做特色化处理
     */
    private function _iOSClickParamsMediaHandle($clickData){
        $_clickData = array();
        switch ($clickData['channel_id']){
            //头条
            case 1:
                if(strpos($clickData['ip'],'.') !== false){
                    $_clickData['ipv4'] = md5($clickData['ip']); 
                } elseif(strpos($clickData['ip'],':') !== false) {
                    $_clickData['ipv6'] = md5($clickData['ip']); 
                }
                $_clickData += array(
                    'plan_id'=>$get['plan_id'],
                    'channel_id'=>$get['channel_id'],
                    'idfa'=>$get['idfa'],
                    'idfa_md5'=>md5($get['idfa']),
                    'mac_md5'=>$get['mac'],         //头条的mac字段是去掉:后的md5
                    'model'=>$get['model'],
                    'appid'=>$get['app_id'],
                    'click_time'=>$get['ts']
                );
                return $_clickData;
                break;
            //广点通
            case 2:
                
            default:
                break;
        }
    }
    
    private function insert($db, $tb_name = '', $new_data = [])
    {
        $fields = implode(',', array_keys($new_data));
        $flags = implode(',', array_fill(0, count($new_data), '?'));
        $values = array_values($new_data);
        $insert_sql = "INSERT INTO {$tb_name}(" . $fields . ") VALUES (" . $flags . ")";
        $res = $db->query($insert_sql, $values);
        return $res;
    }
	
	/**
	 * 接收设备启动消息
	 */
	public function launch(){
	    //$deviceLaunchData = $this->request->getPost(null, FILTER_SANITIZE_MAGIC_QUOTES);
	    $deviceLaunchData = $this->request->getJSON(true);
	     //转为一维数组
		$deviceInfo = $deviceLaunchData['deviceInfo'];
		unset($deviceLaunchData['deviceInfo']);
		$deviceLaunchData = array_merge($deviceLaunchData,$deviceInfo);
	    //print_r($deviceLaunchData);
	    $conf = new \RdKafka\Conf();
	    
	    //TopicConf
	    $topicConf = new \RdKafka\TopicConf();
	    //-1必须等所有brokers同步完成的确认 1当前服务器确认 0不确认，这里如果是0回调里的offset无返回，如果是1和-1会返回offset
	    // 我们可以利用该机制做消息生产的确认，不过还不是100%，因为有可能会中途kafka服务器挂掉
	    $topicConf->set('request.required.acks', 0);
	    
	    $rk = new \RdKafka\Producer($conf);
	    $rk->setLogLevel(LOG_DEBUG);
	    $rk->addBrokers('127.0.0.1:9092');
	    $topic = $rk->newTopic('launch', $topicConf);
	    //echo json_encode($deviceLaunchData);
	    $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode($deviceLaunchData));
	    
	    $len = $rk->getOutQLen();
	    while ($len > 0) {
	        $len = $rk->getOutQLen();
	        $rk->poll(10);
	    }
	    _json(array('code'=>200,'msg'=>'ok'),1);
	    
	    /* $deviceLaunchData = $this->request->getPost(null, FILTER_SANITIZE_MAGIC_QUOTES);
	    try{
    	    $config = ProducerConfig::getInstance();
    	    $config->setMetadataRefreshIntervalMs(10000);
    	    $config->setMetadataBrokerList('127.0.0.1:9092');
    	    $config->setBrokerVersion('1.0.0');
    	    $config->setRequiredAck(1);
    	    $config->setIsAsyn(false);
    	    $config->setProduceInterval(500);
    	    $producer = new Producer();
    	    //$producer->setLogger($logger);
    	    
    	    
    	    $producer->send([
    	            [
    	                'topic' => 'launch',
    	                'value' => json_encode($deviceLaunchData),
    	                'key' => '',
    	            ],
    	        ]);
    	    
    	    _json(array('code'=>200,'msg'=>'ok'),1);
	    } catch (\Exception $e){
	        echo $e->getMessage();
	    } */
	}
	
	/**
	 * 接收设备注册消息
	 */
	public function reg(){
	    //$deviceRegData = $this->request->getPost(null, FILTER_SANITIZE_MAGIC_QUOTES);
	    $deviceRegData = $this->request->getJSON(true);
	//转为一维数组
		$deviceInfo = $deviceRegData['deviceInfo'];
		unset($deviceRegData['deviceInfo']);
		$deviceRegData = array_merge($deviceRegData,$deviceInfo);
	    $conf = new \RdKafka\Conf();
	    
	    //TopicConf
	    $topicConf = new \RdKafka\TopicConf();
	    //-1必须等所有brokers同步完成的确认 1当前服务器确认 0不确认，这里如果是0回调里的offset无返回，如果是1和-1会返回offset
	    // 我们可以利用该机制做消息生产的确认，不过还不是100%，因为有可能会中途kafka服务器挂掉
	    $topicConf->set('request.required.acks', 0);
	    
	    $rk = new \RdKafka\Producer($conf);
	    $rk->setLogLevel(LOG_DEBUG);
	    $rk->addBrokers('127.0.0.1:9092');
	    $topic = $rk->newTopic('reg', $topicConf);
	    
	    $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode($deviceRegData));
	    
	    $len = $rk->getOutQLen();
	    while ($len > 0) {
	        $len = $rk->getOutQLen();
	        $rk->poll(10);
	    }
	    echo json_encode(array('code'=>200,'msg'=>'ok'));
	}

	/**
	 * 接收设备付费消息
	 */
	public function pay(){
	    //$devicePayData = $this->request->getVar(null, FILTER_SANITIZE_MAGIC_QUOTES);
	    $devicePayData = $this->request->getJSON(true);
			//转为一维数组
		$deviceInfo = $devicePayData['deviceInfo'];
		unset($devicePayData['deviceInfo']);
		$devicePayData = array_merge($devicePayData,$deviceInfo);
	    $conf = new \RdKafka\Conf();
	    
	    //TopicConf
	    $topicConf = new \RdKafka\TopicConf();
	    //-1必须等所有brokers同步完成的确认 1当前服务器确认 0不确认，这里如果是0回调里的offset无返回，如果是1和-1会返回offset
	    // 我们可以利用该机制做消息生产的确认，不过还不是100%，因为有可能会中途kafka服务器挂掉
	    $topicConf->set('request.required.acks', 0);
	    
	    $rk = new \RdKafka\Producer($conf);
	    $rk->setLogLevel(LOG_DEBUG);
	    $rk->addBrokers('127.0.0.1:9092');
	    $topic = $rk->newTopic('pay', $topicConf);
	    
	    $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode($devicePayData));
	    
	    $len = $rk->getOutQLen();
	    while ($len > 0) {
	        $len = $rk->getOutQLen();
	        $rk->poll(10);
	    }
	    echo json_encode(array('code'=>200,'msg'=>'ok'));
	}
}
