<?php
/**
 * 安装模块 所有模块都会先检查是否有安装文件
 * 如果没有则跳转至此进行安装操作
 * 前端页面加载完成之后也会首先请求初始化接口检查是否完成安装
 * 若未安装则前端JS强行跳转至此
 * 
 * @package 量U
 */
namespace App\Controllers;

use CodeIgniter\Controller;
use Amp\PrivatePlaceholder;

class Install extends Controller
{

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);
        
        // --------------------------------------------------------------------
        // Preload any models, libraries, etc, here.
        // --------------------------------------------------------------------
        // E.g.:
        // $this->session = \Config\Services::session();
    }

    public function init()
    {
        $install_file_content = trim(file_get_contents(ROOTPATH . 'installed'));
        if ($install_file_content === 'ok') {
            _json([
                'code' => 200,
                'msg' => 'ok',
                'data' => [
                    'installed' => 1
                ]
            ]);
        } else {
            _json([
                'code' => 200,
                'msg' => 'ok',
                'data' => [
                    'installed' => 0
                ]
            ]);
        }
    }

    public function index()
    {
        $step = $this->request->getGetPost('step', FILTER_SANITIZE_MAGIC_QUOTES);
        if ($step < 1) {
            header('Location:/install/index?step=1');
            exit();
        }
        $this->checkInstall($step);
        $this->checkPermission();
        $this->checkPHPEnv();
        switch ($step) {
            case 1:
                file_put_contents(ROOTPATH . 'installed', 1);
                echo view('install/stepOne');
                break;
            case 2:
                echo view('install/stepTwo');
                break;
            case 3:
                echo view('install/stepThree');
                break;
            default:
                exit('参数错误，请停止您的hacker行为');
                break;
        }
    }

    /**
     * 配置写入文件
     */
    private function step2()
    {
    	$install_file_content = trim(file_get_contents(ROOTPATH . 'installed'));
    	if($install_file_content == 1){
    		$post = $this->request->getPost(null, FILTER_SANITIZE_MAGIC_QUOTES);
	        if (isset($post) && ! empty($post)) {
	            // 记录系统配置和管理员信息
	            $s_conf = [
	                'SDKDOMAIN',
	                'SYSUSER',
	                'SYSPWD'
	            ];
	            $sdkdomain = trim($post['sdkdomain']);
	            $sysuser = trim($post['sysuser']);
	            $syspwd = md5(trim($post['syspwd']));
	            $const_config_file_path = APPPATH . '/Config/Constants.php';
	            $const_config_strings = file($const_config_file_path);
	            foreach ($const_config_strings as $line_num => &$line) {
	                if (preg_match_all('(' . implode('|', $s_conf) . ')', $line, $matches)) {
	                    $key = $matches[0][0];
	                    $s_key = strtolower($key);
	                    $line = "defined('{$key}')      || define('{$key}', '{${$s_key}}');\r\n";
	                    $s_conf = array_diff($s_conf, [
	                        $key
	                    ]);
	                }
	            }
	            if (count($s_conf) > 0) {
	                foreach ($s_conf as $key) {
	                    $s_key = strtolower($key);
	                    $const_config_strings[] = "defined('{$key}')      || define('{$key}', '{${$s_key}}');\r\n";
	                }
	            }
	            $const_config_strings = implode('', $const_config_strings);
	            
	            $write_res = @file_put_contents($const_config_file_path, $const_config_strings);
	            if (! $write_res) {
	                // 写入失败 应清空 installed 文件的安装进度
	                @file_put_contents(ROOTPATH . 'installed', '');
	                _json(['code'=>196,'msg'=>'写入配置失败，请检查文件' . $const_config_file_path . '写入权限']);
	            }
	            // 记录数据库配置
	            $hostname = trim($post['dbhost']);
	            $username = trim($post['dbuser']);
	            $password = trim($post['dbpwd']);
	            $database = trim($post['dbname']);
	            $port = trim($post['dbport']);
	            
	            /* $db_config_file_path = APPPATH . '/Config/Database.php';
	            $db_config_strings = file($db_config_file_path);
	            $key = 0;
	            foreach ($db_config_strings as $line_num => $line) {
	                if (! preg_match('/^define\(\s*\'([a-zA-Z_]+)\',([ ]+)/', $line, $match)) {
	                    continue;
	                }
	                $constant = $match[1];
	                $padding = $match[2];
	                
	                switch ($constant) {
	                    case 'hostname':
	                    case 'username':
	                    case 'password':
	                    case 'database':
	                    case 'port':
	                        // $db_config_file[$line_num] = "define( '" . $constant . "'," . $padding . "'" . addcslashes(constant($constant), "\\'") . "' );\r\n";
	                        $db_config_strings[$line_num] = "define( '" . $constant . "'," . $padding . "'" . addcslashes(${$constant}, "\\'") . "' );\r\n";
	                        break;
	                    default:
	                        break;
	                }
	            }
	            unset($line);
	            // 写回配置 判断是否可写
	            if (! is_writable($db_config_file_path)) {
	                // 写入失败 应清空 installed 文件的安装进度
	                @file_put_contents(ROOTPATH . 'installed', '');
	                _json(['code'=>197,'msg'=>'写入配置失败，请检查文件' . $db_config_file_path . '写入权限']);
	            }
	            $handle = fopen($db_config_file_path, 'w');
	            foreach ($db_config_strings as $line) {
	                fwrite($handle, $line);
	            }
	            try {
	                fclose($handle);
	                // chmod( $db_config_file_path, 0666 );
	            } catch (\Exception $e) {
	                echo $e->getMessage();
	            }*/
	            
	            //单独的配置文件 2025-03-08
	            $db_config_file_path = APPPATH . '/Config/DatabaseConfig.php';
	            $db_config = [
	                'hostname' => $hostname,
	                'username' => $username,
	                'password' => $password,
	                'database' => $database,
	                'port' => $port
	            ];
	            $config_content = "<?php\nreturn " . var_export($db_config, true) . ";\n";
	            if (@file_put_contents($db_config_file_path, $config_content)) {
	                //echo "数据库配置文件已成功生成并写入\n";
	            } else {
	                _json(['code'=>197,'msg'=>"无法写入数据库配置文件到 $db_config_file_path, 请确保有权限写入\n"],1);
	            }
	            
	            // 记录系统配置和管理员信息
	            file_put_contents(ROOTPATH . 'installed', 2);
	    	} else {
	    		_json(['code'=>198,'msg'=>'data is empty'],1);
	    	}
        } else {
        	_json(['code'=>199,'msg'=>'step error'],1);
        }
        return true;
    }


    /**
     * 检查各配置文件的写入权限
     * 并使用sudo提升到0777权限
     */
    private function checkPermission()
    {
        $install_file = ROOTPATH . 'installed';
        //$db_config_file = APPPATH . 'Config/Database.php';
        $config_path = APPPATH . 'Config/';
        $const_config_file = APPPATH . 'Config/Constants.php';
        
        $style = '<style>.code{color:#8aa6c1;background-color:#222;padding:10px;border-radius:5px;width:800px;}
                         .btn{margin-left:40px;background-color:#05c;color:#fff;background-image:-webkit-linear-gradient(top,#0088cc,#0055cc);border:0;padding:5px 10px;border-radius:5px;}</style>';
        $tips = '';
        if (! is_writable($install_file)) {
            // 需要修改visudo配置才能使用sudo提权 ，针对apache和nginx分别有不同的配置方式
            // exec('sudo chmod 0766 '.$install_file, $chmod_install_result, $chmod_install_status);
            // chmod($install_file, 0777);
            $tips .= '<li>
                        <p>安装配置文件不可写，请在root权限下使用下列命令修改权限</p>
                        <p class="code"> chmod 0766 ' . $install_file . '</p>
                      </li>';
        }
        /* if (! is_writable($db_config_file)) {
            // exec('sudo chmod 0766 '.$db_config_file, $chmod_db_result, $chmod_db_status);
            $tips .= '<li>
                        <p>数据库配置文件不可写，请在root权限下使用下列命令修改权限</p>
                        <p class="code"> chmod 0766 ' . $db_config_file . '</p>
                      </li>';
        } */
        if (! is_writable($config_path)) {
            // exec('sudo chmod 0766 '.$db_config_file, $chmod_db_result, $chmod_db_status);
            $tips .= '<li>
                        <p>配置目录不可写，请在root权限下使用下列命令修改权限</p>
                        <p class="code"> chmod 0766 ' . $config_path . '</p>
                      </li>';
        }
        if (! is_writable($const_config_file)) {
            // exec('sudo chmod 0766 '.$const_config_file, $chmod_const_result, $chmod_const_status);
            $tips .= '<li>
                        <p>常量配置文件不可写，请在root权限下使用下列命令修改权限</p>
                        <p class="code"> chmod 0766 ' . $const_config_file . '</p>
                      </li>';
        }
        if (! empty($tips)) {
            $html = $style . '<ul>' . $tips . '</ul><button class="btn" onclick="javascript:window.location.reload()">修改后刷新</button>';
            exit($html);
        }
    }

    /**
     * 检查量U系统是否已安装
     * 
     * @param int $step
     *            步骤参数
     */
    private function checkInstall($step)
    {
        $install_file_name = ROOTPATH . 'installed';
        $install_file_content = file_get_contents($install_file_name);
        if (! empty($install_file_content)) {
            if ($install_file_content == 'ok') {
                echo '您已经完成了安装，若您需要重新安装，请先清空根目录下installed文件的内容及删除数据库表';
                exit();
            } else {
                // 步骤参数与当前安装进度不符
                if ($step != $install_file_content) {
                    header('Location:/install/index?step=' . intval($install_file_content));
                    exit();
                }
            }
        } else {
            // 还未进行安装
            if ($step != 1) {
                header('Location:/install/index?step=1');
                exit();
            }
        }
    }

    /**
     * 检查php环境
     */
    private function checkPHPEnv()
    {
        // 检查php版本
        $php_version = phpversion();
        $php_compat = version_compare($php_version, REQUIRED_PHP_VERSION, '>=');
        if ($php_compat === false) {
            echo 'PHP版本不能低于' . REQUIRED_PHP_VERSION . '，您目前安装的PHP版本为' . $php_version;
            exit();
        }
        // 检查是否安装MySQLi驱动
        if (! extension_loaded('mysqli')) {
            echo 'PHP目前未检测到mysqli扩展，请确认是否已经安装并手动加载';
            exit();
        }
    }

    /**
     * 检查MySQL版本
     */
    private function checkMySQLEnv($db)
    {
        $mysql_version = explode('-', $db->getVersion());
        $mysql_version = $mysql_version[0];
        $mysql_compat = version_compare($mysql_version, REQUIRED_MYSQL_VERSION, '>=');
        if ($mysql_compat === false) {
            throw new \Exception('mysql_compat:MySQL或MariaDB版本不能低于' . REQUIRED_MYSQL_VERSION . '，您目前安装的MySQL或MariaDB版本为' . $mysql_version);
        }
    }

    /**
     * 检查各配置参数正确性
     * Ajax调用
     */
    public function checkConfigAndEnvVersion()
    {
        $post = $this->request->getVar(null, FILTER_SANITIZE_MAGIC_QUOTES);     
        
        // 测试系统配置-SDK域名是否可访问 1s超时则说明域名未进行公网部署
        // 使用file_get_contents获取内容需要加http协议
        $sdkDomain = str_replace(array('http://','https://'),'',trim($post['sdkdomain']));
        $sdkDomainUrlHTTP = 'http://'.$sdkDomain . '/ping/index';

        try {
            $opts = array(
                'http' => array(
                    'method' => "GET",
                    'timeout' => 5 // 单位秒
                )
            );
            if (file_get_contents($sdkDomainUrlHTTP) != 'ok') {
                _json(['code' => 107,'msg' => '该域名没有公网解析或者没有指向量U的frontend目录'],1);
            }
        } catch (\Exception $e) {
            _json(['code' => 108,'msg' => '该域名没有公网解析或者没有指向量U的frontend目录'],1);
        }

	    //测试系统配置-SDK域名是否已配置https支持 1s超时则说明域名未部署https
        $sdkDomainUrlHTTPS = 'https://'.$sdkDomain . '/ping/index';

        //2025-03-07暂时关闭 https验证
        /*try {
            $opts = array(
                'http' => array(
                    'method' => "GET",
                    'timeout' => 5 // 单位秒
                )
            );
            if (file_get_contents($sdkDomainUrlHTTPS) != 'ok') {
                _json(['code' => 109,'msg' => '该域名未部署 https '],1);
            }
        } catch (\Exception $e) {
            _json(['code' => 111,'msg' => '该域名未部署 https '],1);
        }*/
        
        // 测试数据库连接
        $dbhost = trim($post['dbhost']);
        $uname = trim($post['dbuser']);
        $pwd = trim($post['dbpwd']);
        $dbname = trim($post['dbname']);
        $port = trim($post['dbport']);
        $custom = [
            'DSN' => '',
            'hostname' => $dbhost,
            'username' => $uname,
            'password' => $pwd,
            'database' => $dbname,
            'DBDriver' => 'MySQLi',
            'DBPrefix' => '',
            'port' => $port
        ];
        
        try {
            unset($custom['database']);
            //dump($custom);

            $db = \Config\Database::connect($custom);
            $db->connect();
            $this->checkMySQLEnv($db);
        } catch (\Exception $e) {
            $error = $e->getMessage();
            $error = iconv('gb2312', 'utf-8', $error);

            if (stripos($error, 'timed') !== false) {
                _json(['code' => 100,'msg' => $error], 1);
            } elseif (stripos($error, 'denied') !== false) {
                _json(['code' => 101,'msg' => $error], 1);
            } elseif (stripos($error, 'Connection refused') !== false) {
                _json(['code' => 102,'msg' => $error], 1);
            } elseif (stripos($error, 'mysql_compat') !== false) {
                _json(['code' => 103,'msg' => $error], 1);
            } else {
            	_json(['code' => 105,'msg' => $error], 1);
            }
        }
        
        // $db->setDatabase($dbname);
        $dbs = $db->query("show databases")->getResultArray();
        if (is_array($dbs) && count($dbs) > 0) {
            $dbs = array_column($dbs, 'Database');
        }
        if (in_array($dbname, $dbs)) {
            _json(['code' => 105,'msg' => 'database ' . $dbname . ' does exists already'], 1);
        }

        //测试Redis连接
        $redishost = trim($post['redishost']);
        $redisport = trim($post['redisport']);
        try{
            $redis = stream_socket_client('tcp://'.$redishost.':'.$redisport, $errno, $errstr, 5);
        } catch(\Exception $e){
            _json(['code' => 106,'msg' => '连接redis服务器失败: ' . $errstr],1);
        }
        

        // 环境通过检测开始写入配置
        $write_res = $this->step2();
        if($write_res){
        	// 配置写入完成 创建installed文件
        	// @是有必要的 否则因为权限无法写入会抛出警告而无法获取写入结果的值
        	$write_res = @file_put_contents(ROOTPATH . 'installed', 2);
        	if ($write_res) {
	            _json(['code' => 200,'msg' => 'ok']);
	        } else {
	            _json(['code' => 106,'msg' => '写安装文件失败，请检查站点根目录' . ROOTPATH . '下installed文件的写入权限']);
	        }
        }
    }

    /**
     * 修改installed文件为step3
     * Ajax
     */
    public function step3(){
    	$write_res = @file_put_contents(ROOTPATH . 'installed', 3);
    	if ($write_res) {
            _json(['code' => 200,'msg' => 'ok']);
        } else {
            _json(['code' => 106,'msg' => '写安装文件失败，请检查站点根目录' . ROOTPATH . '下installed文件的写入权限']);
        }
    }

    /**
     * 检查数据库表安装情况
     * Ajax
     */
    public function checkDbSchema()
    {
        try {
            // 创建数据库
            $dbconf = config('Database');
            $custom = $dbconf->default;
            //var_dump($custom);
            $database = $custom['database'];
            unset($custom['database']);
            $db = \Config\Database::connect($custom);
            $db->connect();
            $createDbSql = "CREATE DATABASE IF NOT EXISTS `{$database}` default character set utf8mb4 collate utf8mb4_general_ci";
            $createDbResult = $db->query($createDbSql);
            // TODO 创建数据库成功给前端提示
            $db->setDatabase($database);
            // 创建数据表
            $queries = $this->getDbSchema();
            if (! is_array($queries)) {
                $queries = explode(';', $queries);
                $queries = array_filter($queries);
            }
            
            $schemaResult = [];
            if (is_array($queries) && count($queries) > 0) {
                foreach ($queries as $query) {
                    $res = $db->query($query);
                    //正则匹配表名 TODO 验证SQL执行结果
                    preg_match('/`(.*)`\s*\(/U', $query, $matches); // U修饰符限制贪婪
                    //var_dump($matches);
                    if(is_array($matches) && isset($matches[1])){
                        $temp = [];
                        $temp['tableName'] = $matches[1];
                        $temp['installResult'] = $res->resultID ? 1 : 0;
                        $schemaResult[] = $temp;
                    }
                }
            }
            // 数据表写入初始化数据
            $addUserSql = "INSERT INTO `u_user` (`username`, `pwd`, `add_time`) VALUES ('" . SYSUSER . "', '" . SYSPWD . "', '" . date('Y-m-d H:i:s', time()) . "');";
            $db->query($addUserSql);
            $addConfSql = "INSERT INTO `u_conf` (`conf_key`, `conf_value`) VALUES ('SDKDOMAIN', '" . SDKDOMAIN . "');";
            $db->query($addConfSql);

            $channel_name  = $channel_link_tpl = [];
            //头条监测链接模板（TIMESTAMP改为ts 以下皆同）
            $channel_name[] = "今日头条";
            $byte_click_monitor_link_tpl = '{"android":"aid=__AID__&cid=__CID__&imei_md5=__IMEI__&mac_md5=__MAC1__&oaid_md5=__OAID_MD5__&androidid_md5=__ANDROIDID__&ip=__IP__&ipv6=__IPV6__&ua=__UA__&model=__MODEL__&geo=__GEO__&os=__OS__&ts=__TS__&callback_url=__CALLBACK_URL__","ios":"aid=__AID__&cid=__CID__&idfa_md5=__IDFA_MD5__&mac_md5=__MAC1__&caid=__CAID__&ip=__IP__&ipv6=__IPV6__&ua=__UA__&model=__MODEL__&geo=__GEO__&os=__OS__&ts=__TS__&callback_url=__CALLBACK_URL__"}';
            $channel_link_tpl[] = $byte_click_monitor_link_tpl;
            //广点通监测链接模板
            $channel_name[] = "广点通";
            $tencent_click_monitor_link_tpl = '{"android":"imei_md5=__MUID__&androidid_md5=__HASH_ANDROID_ID__&oaid_md5=__HASH_OAID__&ip=__IP__&ipv6=__IPV6__&ua=__USER_AGENT__&model=__MODEL__&ts=__CLICK_TIME__&callback_url=__CALLBACK__","ios":"idfa_md5=__MUID__&caid=__QAID_CAA__&ip=__IP__&ipv6=__IPV6__&ua=__USER_AGENT__&model=__MODEL__&ts=__CLICK_TIME__&callback_url=__CALLBACK__"}';
            $channel_link_tpl[] = $tencent_click_monitor_link_tpl;
            //百度信息流监测链接模板
            $channel_name[] = "百度信息流";
            $baidu_click_monitor_link_tpl = '{"android": "imei_md5=__IMEI__&androidid_md5=__ANDROIDID__&oaid_md5=__OAID_MD5__&mac_md5=__MAC1__&ip=__IP__&ipv6=__IPV6__&ua=__UA__&model=__MODEL__&ts=__TS__&callback_url=__CALLBACK_URL__","ios": "idfa=__IDFA__&caid=__CAID__&mac_md5=__MAC1__&ip=__IP__&ipv6=__IPV6__&ua=__UA__&model=__MODEL__&ts=__TS__&callback_url=__CALLBACK_URL__"}';
            $channel_link_tpl[] = $baidu_click_monitor_link_tpl;
            //阿里汇川
            $channel_name[] = "阿里汇川";
            $uc_click_monitor_link_tpl = '{"android": "imei_md5={IMEI_SUM1}&oaid_md5={OAID_SUM}&androidid_md5={ANDROIDID_SUM1}&mac_md5={MAC_SUM2}&ip={IP}&ua={UA}&ts={UX_TS}&callback_url={CALLBACK_URL}","ios": "idfa={IDFA1}&caid={CAID}&mac_md5={MAC_SUM2}&ip={IP}&ua={UA}&ts={UX_TS}&callback_url={CALLBACK_URL}"}';
            $channel_link_tpl[] = $uc_click_monitor_link_tpl;
            //小米
            $channel_name[] = "小米";
            $xiaomi_click_monitor_link_tpl = '{"android": "imei_md5=__IMEI__&imei2_md5=__IMEI2__&meid_md5=__MEID__&oaid=__OAID__&androidid_md5=__ANDROIDID__&ua=__UA__&ip=__IP__&ts=__TS__&app_id=__APPID__&adid=__ADID__&campaign_id=__CAMPAIGNID__&callback_url=__CALLBACK__&sign=__SIGN__"}';
            $channel_link_tpl[] = $xiaomi_click_monitor_link_tpl;

            $add_time = date('Y-m-d H:i:s',time());
            foreach($channel_name as $k=>$v){
                $addChannelSql = "INSERT INTO `u_channel`(`channel_name`, `click_monitor_link_tpl`, `add_time`) VALUES ('".$v."','".$channel_link_tpl[$k]."','".$add_time."')";
                //echo $addChannelSql;
                $db->query($addChannelSql);
            }

            // 写安装文件
            file_put_contents(ROOTPATH . 'installed', 'ok');
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        echo json_encode([
            'code' => 200,
            'msg' => 'ok',
            'data' => $schemaResult
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * 获取数据库生成SQL
     */
    private function getDbSchema()
    {
        // 统计表
        $lion_u_tables = "CREATE TABLE IF NOT EXISTS `statistics_base` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `app_id` int(11) NOT NULL COMMENT '应用ID',
          `plan_id` int(11) NOT NULL,
          `channel_id` int(11) NOT NULL,
          `click_count` int(11) NOT NULL COMMENT '点击总数',
          `launch_count` int(11) NOT NULL DEFAULT '0' COMMENT '启动次数',
          `active_count` int(11) NOT NULL DEFAULT '0' COMMENT '激活设备数',
          `reg_count` int(11) NOT NULL DEFAULT '0' COMMENT '注册次数',
          `onlyreg_count` int(11) NULL DEFAULT 0 COMMENT '去重注册设备数',
          `vibrant_count` int(11) NULL DEFAULT 0 COMMENT '活跃设备数',
          `pay_amount` float NOT NULL DEFAULT '0' COMMENT '当天总付费金额 分',
          `pay_count` int(11) NOT NULL DEFAULT '0' COMMENT '当天总付费次数',
          `stat_date` date NOT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1 ;
            
        CREATE TABLE IF NOT EXISTS `statistics_pay` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `app_id` mediumint(9) NOT NULL DEFAULT '0' COMMENT '应用ID',
          `plan_id` int(11) NOT NULL DEFAULT '0',
          `channel_id` mediumint(9) NOT NULL DEFAULT '0',
          `pay_amount` float NOT NULL DEFAULT '0' COMMENT '当天付费总金额',
          `pay_count` int(11) NOT NULL DEFAULT '0' COMMENT '当天付费总次数',
          `pay_total_devices` int(11) NULL DEFAULT 0 COMMENT '当天总付费设备数（包括新旧设备）',
          `pay_active_amount` float NULL DEFAULT 0 COMMENT '当天激活设备当天付费总金额（激活付费）',
          `pay_active_count` int(11) NULL DEFAULT 0 COMMENT '当天激活设备当天付费次数（激活付费）',
          `pay_new_amount` float NULL DEFAULT 0 COMMENT '首次付费总金额（新增付费）',
          `pay_new_devices` int(11) NULL DEFAULT 0 COMMENT '首次付费总设备数（新增付费）',
          `pay_days` mediumint(9) DEFAULT '1' COMMENT '激活日期至付费日期的间隔天数 当天激活当天付费 则为1 次日即为2 以此类推',
          `active_date` date NOT NULL COMMENT '激活日期',
          `pay_date` date NULL DEFAULT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COMMENT='付费记录表-用于统计LTV' AUTO_INCREMENT=1 ;
            
        CREATE TABLE IF NOT EXISTS `statistics_retention` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `app_id` int(11) NOT NULL DEFAULT '0' COMMENT '应用ID',
          `plan_id` int(11) NOT NULL DEFAULT '0',
          `channel_id` smallint(6) NOT NULL DEFAULT '0',
          `retention_count` int(10) unsigned NOT NULL DEFAULT '0',
          `retention_days` smallint(5) unsigned NOT NULL DEFAULT '0',
          `active_date` date DEFAULT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COMMENT='留存数据表' AUTO_INCREMENT=1 ;
            
        CREATE TABLE IF NOT EXISTS `u_app` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `app_name` varchar(50) NOT NULL COMMENT '应用名',
          `package_name` varchar(100) NOT NULL COMMENT '包名',
          `app_os` int(11) NOT NULL COMMENT '应用所在平台 1 Android 2 iOS 3 H5 4 小程序 5 Unity',
          `app_status` tinyint(4) NOT NULL COMMENT 'app显示状态 1 显示 0 隐藏',
          `app_step` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'app添加所属步骤 1 已填写名称等信息  2 已接入SDK 等待数据回传 3 接入完成',
          `app_event` varchar(100) NOT NULL COMMENT 'app回传事件',
          `add_time` datetime NOT NULL COMMENT '添加应用时间',
          `update_time` datetime NOT NULL COMMENT '修改应用时间',
          `note` varchar(100) NOT NULL COMMENT '修改应用描述',
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1 ;
            
        CREATE TABLE IF NOT EXISTS `u_channel` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `channel_name` varchar(50) NOT NULL COMMENT '渠道名称',
          `click_monitor_link_tpl` varchar(1000) NOT NULL COMMENT '点击监测链接模板',
          `status` tinyint NULL DEFAULT 1 COMMENT '0 不显示 1 显示',
          `add_time` datetime NOT NULL COMMENT '添加时间',
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1 ;
            
        CREATE TABLE IF NOT EXISTS `u_plan` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `plan_name` varchar(50) NOT NULL COMMENT '计划名称',
          `app_id` int(11) NOT NULL COMMENT '应用ID',
          `channel_id` mediumint(9) NOT NULL COMMENT '渠道ID',
          `add_time` datetime NOT NULL COMMENT '添加时间',
          `click_monitor_link` varchar(1000) NOT NULL COMMENT '点击监测链接',
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1 ;

        CREATE TABLE IF NOT EXISTS `u_user` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `username` varchar(50) NOT NULL COMMENT '管理用户帐号',
          `pwd` varchar(50) NOT NULL COMMENT '管理用户密码',
          `role` int(11) NOT NULL COMMENT '管理用户角色ID 默认1超级管理员' DEFAULT 1,
          `add_time` datetime NOT NULL COMMENT '添加时间',
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS `u_conf` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `conf_key` varchar(50) NOT NULL COMMENT '配置项',
          `conf_value` varchar(50) NOT NULL COMMENT '配置值',
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4;";
        

        // 日志表
        $log_tables = "CREATE TABLE IF NOT EXISTS `log_android_active` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `imei_md5` varchar(100) DEFAULT NULL,
          `androidid_md5` varchar(100) DEFAULT NULL,
          `mac_md5` varchar(100) DEFAULT NULL,
          `oaid_md5` varchar(100) DEFAULT NULL,
          `ip` varchar(100) DEFAULT NULL,
          `external_ip` varchar(100) DEFAULT NULL,
          `appid` int(11) DEFAULT NULL,
          `plan_id` int(11) DEFAULT NULL,
          `channel_id` int(11) DEFAULT NULL,
          `active_time` datetime DEFAULT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1 ;
        
        CREATE TABLE IF NOT EXISTS `log_android_click_data` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `mac_md5` varchar(100) DEFAULT NULL,
          `androidid_md5` varchar(100) CHARACTER SET utf8 DEFAULT NULL,
          `imei_md5` varchar(100) DEFAULT NULL,
          `oaid_md5` varchar(100) DEFAULT NULL,
          `ip` varchar(16) DEFAULT NULL,
          `ipv6` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
          `external_ip` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
          `ua` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
          `model` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '手机型号',
          `geo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '经纬度',
          `appid` int(11) DEFAULT NULL,
          `channel_id` int(11) DEFAULT NULL,
          `plan_id` int(11) DEFAULT NULL,
          `click_time` datetime DEFAULT NULL,
          `create_time` datetime NULL DEFAULT NULL COMMENT '监测数据写入时间',
          `callback_url` varchar(300) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1 ;
        
        CREATE TABLE IF NOT EXISTS `log_android_launch` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `imei_md5` varchar(100) DEFAULT NULL,
            `androidid_md5` varchar(100) DEFAULT NULL,
            `mac_md5` varchar(100) DEFAULT NULL,
            `oaid_md5` varchar(100) DEFAULT NULL,
            `ip` varchar(100) DEFAULT NULL,
            `external_ip` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
            `appid` int(11) DEFAULT NULL,
            `plan_id` int(11) DEFAULT NULL,
            `channel_id` int(11) DEFAULT NULL,
            `launch_time` datetime DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1 ;
        
        CREATE TABLE IF NOT EXISTS `log_android_pay`  (
            `id` int NOT NULL AUTO_INCREMENT,
            `imei_md5` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
            `androidid_md5` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
            `mac_md5` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
            `oaid_md5` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
            `ip` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
            `external_ip` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
            `appid` int NULL DEFAULT NULL,
            `plan_id` int NULL DEFAULT NULL,
            `channel_id` int NULL DEFAULT NULL,
            `pay_time` datetime NULL DEFAULT NULL,
            `pay_amount` int NULL DEFAULT NULL,
            `active_time` datetime NULL DEFAULT NULL,
            PRIMARY KEY (`id`) USING BTREE
        ) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;
        SET FOREIGN_KEY_CHECKS = 1; 

        CREATE TABLE IF NOT EXISTS `log_android_reg`  (
            `id` int NOT NULL AUTO_INCREMENT,
            `imei_md5` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
            `androidid_md5` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
            `mac_md5` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
            `oaid_md5` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
            `ip` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
            `external_ip` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
            `appid` int NULL DEFAULT NULL,
            `plan_id` int NULL DEFAULT NULL,
            `channel_id` int NULL DEFAULT NULL,
            `reg_time` datetime NULL DEFAULT NULL,
            PRIMARY KEY (`id`) USING BTREE
            ) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;
        SET FOREIGN_KEY_CHECKS = 1;

        CREATE TABLE IF NOT EXISTS `log_android_regonly`  (
            `id` int NOT NULL AUTO_INCREMENT,
            `imei_md5` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
            `androidid_md5` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
            `mac_md5` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
            `oaid_md5` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
            `ip` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
            `external_ip` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
            `appid` int NULL DEFAULT NULL,
            `plan_id` int NULL DEFAULT NULL,
            `channel_id` int NULL DEFAULT NULL,
            `reg_time` datetime NULL DEFAULT NULL,
            PRIMARY KEY (`id`) USING BTREE,
            UNIQUE INDEX `imei_md5`(`imei_md5` ASC) USING BTREE,
            UNIQUE INDEX `oaid_md5`(`oaid_md5` ASC) USING BTREE
            ) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '去重注册设备表' ROW_FORMAT = Dynamic;
        SET FOREIGN_KEY_CHECKS = 1;

        CREATE TABLE IF NOT EXISTS `log_ios_active` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `uuid_md5` varchar(100) DEFAULT NULL,
          `idfa_md5` varchar(100) DEFAULT NULL,
          `mac_md5` varchar(100) DEFAULT NULL,
          `model` varchar(20) DEFAULT NULL,
          `ip` varchar(100) DEFAULT NULL,
          `external_ip` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
          `appid` int(11) DEFAULT NULL,
          `plan_id` int(11) DEFAULT NULL,
          `channel_id` int(11) DEFAULT NULL,
          `active_time` datetime DEFAULT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1;
        
        CREATE TABLE IF NOT EXISTS `log_ios_click_data` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `idfa` varchar(100) DEFAULT NULL,
          `idfa_md5` varchar(100) CHARACTER SET utf8 DEFAULT NULL,
          `mac` varchar(100) DEFAULT NULL,
          `mac_md5` varchar(100) DEFAULT NULL,
          `ua` varchar(100) DEFAULT NULL,
          `ip` varchar(16) DEFAULT NULL,
          `external_ip` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
          `model` varchar(20) DEFAULT NULL COMMENT 'iOS 设备型号',
          `CAID1` varchar(100) DEFAULT NULL COMMENT '中国广告协会互联网广告标识20201230版',
          `CAID2` varchar(100) DEFAULT NULL COMMENT '预留',
          `appid` int(11) DEFAULT NULL,
          `channel_id` int(11) DEFAULT NULL,
          `plan_id` int(11) DEFAULT NULL,
          `click_time` datetime DEFAULT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1;
        
        CREATE TABLE IF NOT EXISTS `log_ios_launch` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `uuid_md5` varchar(100) DEFAULT NULL,
          `idfa_md5` varchar(100) DEFAULT NULL,
          `mac_md5` varchar(100) DEFAULT NULL,
          `model` varchar(20) DEFAULT NULL,
          `ip` varchar(100) DEFAULT NULL,
          `external_ip` varchar(100) DEFAULT NULL,
          `appid` int(11) DEFAULT NULL,
          `plan_id` int(11) DEFAULT NULL,
          `channel_id` int(11) DEFAULT NULL,
          `launch_time` datetime DEFAULT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1;
        
        CREATE TABLE IF NOT EXISTS `log_ios_pay`  (
            `id` int NOT NULL AUTO_INCREMENT,
            `uuid_md5` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
            `idfa_md5` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
            `model` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
            `ip` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
            `external_ip` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
            `appid` int NULL DEFAULT NULL,
            `plan_id` int NULL DEFAULT NULL,
            `channel_id` int NULL DEFAULT NULL,
            `pay_time` datetime NULL DEFAULT NULL,
            `pay_amount` int NULL DEFAULT NULL,
            `active_time` datetime NULL DEFAULT NULL,
            PRIMARY KEY (`id`) USING BTREE
            ) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;
        
        CREATE TABLE IF NOT EXISTS `log_ios_reg`  (
            `id` int NOT NULL AUTO_INCREMENT,
            `uuid_md5` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
            `idfa_md5` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
            `model` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
            `ip` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
            `external_ip` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
            `appid` int NULL DEFAULT NULL,
            `plan_id` int NULL DEFAULT NULL,
            `channel_id` int NULL DEFAULT NULL,
            `reg_time` datetime NULL DEFAULT NULL,
            PRIMARY KEY (`id`) USING BTREE
            ) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;
        
        CREATE TABLE IF NOT EXISTS `log_ios_regonly`  (
            `id` int NOT NULL AUTO_INCREMENT,
            `uuid_md5` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
            `idfa_md5` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
            `model` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
            `ip` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
            `external_ip` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
            `appid` int NULL DEFAULT NULL,
            `plan_id` int NULL DEFAULT NULL,
            `channel_id` int NULL DEFAULT NULL,
            `reg_time` datetime NULL DEFAULT NULL,
            PRIMARY KEY (`id`) USING BTREE
            ) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic
        ";  //内部SQL语句最后不能加 ; 号

        return $lion_u_tables.$log_tables;
    }
}
