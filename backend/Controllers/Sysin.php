<?php
namespace App\Controllers;

// use App\Models\NewsModel;
class Sysin extends NeedloginController
{

    /**
     * 查看文档
     * 新窗口打开连接
     */
    public function doc()
    {
        echo '这是文档';
    }

    /**
     * 下载SDK
     * 新窗口打开链接
     */
    public function downloadSDK()
    {
        $params = $this->request->getVar(null, FILTER_SANITIZE_MAGIC_QUOTES);
        $app_id = isset($params['app_id']) && (intval($params['app_id']) > 0) ? intval($params['app_id']) : exit('app id empty');
        $sql = "SELECT id,app_name,app_os FROM u_app WHERE id=? LIMIT 0,1";
        $db = \Config\Database::connect();
        $query = $db->query($sql,[$app_id]);
        $app = $query->getRowArray();
        if($app['app_os'] == 1){
            //Android
            if(!extension_loaded('zip')){
                $extension_dir = ini_get('extension_dir');
                $zip_so_path = $extension_dir.'/zip.so';
                if(file_exists($zip_so_path)){
                    //扩展已安装
                    if(function_exists('dl')){
                        if(!dl('zip.so')){
                            _json(['code' => 199,'msg' => '加载 zip 扩展失败,请手动修改 php.ini 配置加载 zip 扩展'], 1);
                        }
                    } else {
                        _json(['code' => 199,'msg' => '您使用的SAPI不支持 dl 自动加载，请手动修改 php.ini 配置加载 zip 扩展'], 1);
                    }
                } else {
                    //扩展未安装 php调用shell脚本方式在yum安装带上-y参数会产生Service Unavailable错误 暂没有找到解决方案
                    //$install_zip_sh = ROOTPATH . 'envsoft/compile_install_zip.sh > /dev/null';
                    //exec('sudo '.$install_zip_sh, $install_zip_result, $install_zip_status);
                    $install_zip_sh = ROOTPATH . 'envsoft/start.sh';
                    _json(['code' => 199,'msg' => "系统还未安装 php-zip 扩展,请使用root用户手动执行{$install_zip_sh}进行系统初始化"], 1);
                }
            }
            
            $source_file = $_SERVER["DOCUMENT_ROOT"].'/download_sdk/LionsU_Android.aar';
            if(file_exists($source_file)){
                $dest_file = '/tmp/LionsU_Android_'.$app_id.'.aar';
                $cp_result = copy($source_file,$dest_file);
                $zip = new \ZipArchive;
                if ($zip->open($dest_file) === TRUE) {
                    $conf = array(
                        'host'=>SDKDOMAIN,
                        'appid'=>$app_id
                    );
                    $conf = json_encode($conf);
                    $zip->addFromString('assets/lion-u-config.json', $conf);
                    $zip->close();
                    //下载SDK
                    header('Content-Description: File Transfer');
                    header('Content-Type: application/vnd.android.package-archive');
                    header('Content-Disposition: attachment; filename='.basename($dest_file));
                    header('Content-Transfer-Encoding: binary');
                    header('Expires: 0');
                    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                    header('Pragma: public');
                    header('Content-Length: ' . filesize($dest_file));
                    ob_clean();
                    flush();
                    readfile($dest_file);
                    unlink($dest_file);
                } else {
                    exit('打开sdk包失败');
                }
            } else {
                exit('Android SDK源文件不存在');
            }
        } elseif($app['app_os'] == 2) {
            //iOS
            header('Location:/download_sdk/LionsU_iOS.rar');
            exit; //一定要加这行才会进行跳转
        }
    }

    /**
     * 监测zookeeper & kafka & spark系统软件的运行情况
     */
    public function kafkaAndSpark()
    {
        $data = array(
            'cores' => 0,
            'mem' => 0,
            'php-kafka' => 0,
            'zookeeper' => 0,
            'kafka' => 0,
            'spark' => 0
        );
        // 检测CPU核数和内存大小
        $check_cpus_shell = "grep 'physical id' /proc/cpuinfo | sort -u | wc -l"; // 物理CPU个数
        $check_cpu_cores_shell = "grep 'core id' /proc/cpuinfo | sort -u | wc -l"; // 单个CPU核数
        // 检测CPU逻辑核数（现代CPU独立物理CPU之上可以搭载多个核心，而每个核心又可以分化出虚拟化的逻辑CPU核心）
        $check_siblings_shell = "grep 'siblings' /proc/cpuinfo | wc -l"; // 逻辑CPU总数

        exec($check_cpus_shell, $check_cpus_result, $check_cpus_status);
        exec($check_cpu_cores_shell, $check_cpu_cores_result, $check_cpu_cores_status);
        exec($check_siblings_shell, $check_siblings_result, $check_siblings_status);

        // 物理核数
        if (! $check_cpus_status && ! $check_cpu_cores_status) {
            if (is_array($check_cpus_result) && is_array($check_cpu_cores_result)) {        //如果每个物理CPU的核心数不一致 则这种直接相乘算法不适合
                $data['cores'] = $check_cpus_result[0] * $check_cpu_cores_result[0];
            }
        }
        // 逻辑核数
        if(! $check_siblings_status){
            if(is_array($check_siblings_result)){
                $data['vcpu'] = $check_siblings_result[0];
            }
        }
        $check_mem_shell = "cat /proc/meminfo | grep MemTotal";
        exec($check_mem_shell, $check_mem_result, $check_mem_status);
        if (! $check_mem_status) {
            if (is_array($check_mem_result) && count($check_mem_result) > 0) {
                $_mem_ = explode(':', $check_mem_result[0]);
                $_mem = round(trim(str_replace('kB', '', $_mem_[1])) / 1000 / 1000); // GB
                $data['mem'] = $_mem;
            }
        }
        // 检测php-kafka扩展安装情况
        if(extension_loaded('rdkafka')){
            $data['php-kafka'] = 1;
        }
        
        // 监测zookeeper运行情况 zookeeper默认2181端口
        $check_zookeeper_shell = "netstat -tnlp | grep 2181";
        exec($check_zookeeper_shell, $check_zookeeper_result, $check_zookeeper_status);
        
        if (! $check_zookeeper_status) {
            if (is_array($check_zookeeper_result) && count($check_zookeeper_result) > 0) {
                $data['zookeeper'] = 1;
            }
        }
        // 监测kafka运行情况 kafka默认9092端口
        $check_kafka_shell = "netstat -tnlp | grep  9092";
        exec($check_kafka_shell, $check_kafka_result, $check_kafka_status);
        if (! $check_kafka_status) {
            if (is_array($check_kafka_result) && count($check_kafka_result) > 0) {
                $data['kafka'] = 1;
            }
        }
        // 监测spark运行情况 spark默认7077端口
        $check_spark_shell = "netstat -tnlp | grep  7077";
        exec($check_spark_shell, $check_spark_result, $check_spark_status);
        if (! $check_spark_status) {
            if (is_array($check_spark_result) && count($check_spark_result) > 0) {
                $data['spark'] = 1;
            }
        }
        _json([
            'code' => 200,
            'msg' => 'ok',
            'data' => $data
        ]);
    }

    /**
     * 启动zookeeper & kafka & spark系统软件
     * start.sh 检查zip.so 和 rdkafka.so 两个php扩展并启动相关软件
     */
    public function startKafkaAndSpark()
    {
        _json(['code' => 199,'msg' => '请使用 root 用户执行' . ROOTPATH . 'envsoft/start.sh脚本启动系统，启动后请刷新页面'], 1);
        /* $post = $this->request->getPost(null, FILTER_SANITIZE_MAGIC_QUOTES);
        $soft = $post['soft'];
        switch ($soft) {
            case 'php-kafka':
                //首先检测系统中是否安装了 rdkafka 扩展
                $extension_dir = ini_get('extension_dir');
                $rdkafka_ext_path = $extension_dir .'/rdkafka.so';
                if(file_exists($rdkafka_ext_path)){
                    //已安装扩展则进行加载
                    if(!(bool)ini_get("enable_dl") || (bool)ini_get("safe_mode")){
                        _json(['code' => 199,'msg' => '请打开php.ini配置文件并将 enable_dl 设置为On或关闭安全模式'], 1);
                    } else {
                        if(function_exists('dl')){
                            if(dl('rdkafka.so')){
                                _json(['code' => 200,'msg' => '加载成功'], 1);
                            } else {
                                _json(['code' => 199,'msg' => '加载rdkafka扩展失败,请手动修改php.ini配置加载rdkafka扩展'], 1);
                            }
                        } else {
                            _json(['code' => 199,'msg' => '您使用的SAPI不支持自动加载，请手动修改php.ini配置加载rdkafka扩展'], 1);
                        }
                    }
                } else {
                    //未安装扩展则执行安装脚本进行安装
                    //TODO 无法通过调用脚本的方式进行安装 暂无解决方案
                    $install_rdkafka_sh = ROOTPATH . 'envsoft/compile_install_rdkafka.sh';
                    //exec('sudo '.$install_rdkafka_sh,$install_rdkafka_result,$install_rdkafka_status);
                    //var_dump($install_rdkafka_result);
                    //var_dump($install_rdkafka_status);
                    _json(['code' => 199,'msg' => '系统无法安装rdkafka扩展，请使用root用户执行 '.$install_rdkafka_sh.' 进行手动安装'], 1);
                }
                
                break;
            case 'zookeeper':
                // 检查shell脚本可执行权限
                // 根据端口检查服务是否启动  没有查找到结果 exec第三个变量为1 如果成功查询到 则为0 
                exec("netstat -tnlp | grep  2181", $zookeeper_port_result, $zookeeper_port_status);
                if ($zookeeper_port_status != 0) {
                    // 以服务形式启动zookeeper以免卡住php进程 sudo是最后的方案 因为需要修改/etc/sudoers实际上是增加了复杂度 最方便快捷的方法是修改脚本文件的权限为0755
                    // 后来发现apache用户无法以daemon方式启动shell脚本 看来sudo是唯一的解决方案了
                    $zookeeper_sh = ROOTPATH . 'envsoft/kafka_2.12-2.6.0/bin/zookeeper-server-start.sh';
                    $zookeeper_conf = ROOTPATH . 'envsoft/kafka_2.12-2.6.0/config/zookeeper.properties&';
                    $start_zookeeper_shell = 'sudo ' . $zookeeper_sh . ' -daemon ' . $zookeeper_conf;
                    
                    exec($start_zookeeper_shell, $start_zookeeper_result, $start_zookeeper_status);
                    
                    // echo $start_zookeeper_shell;
                    // dump($start_zookeeper_status);
                    // dump($start_zookeeper_result);
                    // echo get_current_user();
                    // exit;
                    if (! $start_zookeeper_status) {
                        // 以服务形式启动后前端不会再接收到输出信息所以需要根据端口检查服务是否启动-但启动需要缓冲时间故需要等待几秒（暂无其他更好的解决方案）
                        sleep(2);
                        exec("netstat -tnlp | grep  2181", $zookeeper_port_result, $zookeeper_port_status);
                        if ($zookeeper_port_status != 0) {
                            // 若启动失败 则以非服务方式再启动一次 收集输出错误信息
                            $start_zookeeper_shell = 'sudo ' . $zookeeper_sh . ' ' . $zookeeper_conf;
                            exec($start_zookeeper_shell, $start_zookeeper_result, $start_zookeeper_status);
                            
                            //var_dump($start_zookeeper_result);
                            //exit;
                            if (is_array($start_zookeeper_result) && count($start_zookeeper_result) > 0) {
                                foreach ($start_zookeeper_result as $k => $_line) {
                                    if (stripos($_line, '] ERROR') !== false) {
                                        $_line = isset($start_zookeeper_result[$k + 1]) ? $_line . "\r\n" . $start_zookeeper_result[$k + 1] : $_line;
                                        _json(['code' => 199,'msg' => '启动zookeeper失败' . $_line], 1);
                                    } elseif (stripos($_line, 'insufficient memory') !== false) {
                                        _json(['code' => 199,'msg' => '启动zookeeper失败,内存不足'], 1);
                                    }
                                }
                                _json(['code' => 199,'msg' => '启动zookeeper失败,其他原因'], 1);
                            } else {
                                if ($start_zookeeper_status) {
                                    _json(['code' => 200,'msg' => '启动zookeeper成功'], 1);
                                } else {
                                    _json(['code' => 199,'msg' => '启动zookeeper失败,请检查apache或者nginx等webserver用户是否拥有sudo权限'], 1);
                                }
                            }
                        } else {
                            _json(['code' => 200,'msg' => '启动zookeeper成功'], 1);
                        }
                    } else {
                        _json(['code' => 199,'msg' => '启动zookeeper失败,请检查webServer用户及php-fpm用户权限或联系运维手动启动'], 1);
                    }
                } else {
                    _json(['code' => 198,'msg' => 'zookeeper已启动,请勿重复启动'], 1);
                }
                break;
            case 'kafka':
                // 先判断zookeeper是否开启
                exec("netstat -tnlp | grep  2181", $zk_result, $zk_status);
                if($zk_status != 0){
                    _json(['code' => 198,'msg' => '请先启动zookeeper'], 1);
                }
                // 根据端口检查服务是否启动
                exec("netstat -tnlp | grep  9092", $kafka_port_result, $kafka_port_status);
                if ($kafka_port_status != 0) {
                    // 以服务形式启动kafka以免卡住php进程
                    $kafka_sh = ROOTPATH . 'envsoft/kafka_2.12-2.6.0/bin/kafka-server-start.sh';
                    $kafka_conf = ROOTPATH . 'envsoft/kafka_2.12-2.6.0/config/server.properties&';
                    $start_kafka_shell = 'sudo ' . $kafka_sh . ' -daemon ' . $kafka_conf;
                    
                    exec($start_kafka_shell, $start_kafka_result, $start_kafka_status);
                    // dump($start_kafka_status);
                    // dump($start_kafka_result);
                    // exit;
                    if (! $start_kafka_status) {
                        // 以服务形式启动后前端不会再接收到输出信息所以需要根据端口检查服务是否启动-但启动需要缓冲时间故需要等待几秒（暂无其他更好的解决方案）
                        sleep(2);
                        exec("netstat -tnlp | grep  9092", $kafka_port_result, $kafka_port_status);
                        if ($kafka_port_status != 0) {
                            // 若启动失败 则以非服务方式再启动一次 收集输出错误信息
                            $start_kafka_shell = 'sudo ' . $kafka_sh . ' ' . $kafka_conf;
                            exec($start_kafka_shell, $start_kafka_result, $start_kafka_status);
                            // var_dump($start_kafka_result);
                            // exit;
                            if (is_array($start_kafka_result) && count($start_kafka_result) > 0) {
                                foreach ($start_kafka_result as $k => $_line) {
                                    if (stripos($_line, '] ERROR') !== false) {
                                        $_line = isset($start_kafka_result[$k + 1]) ? $_line . "\r\n" . $start_kafka_result[$k + 1] : $_line;
                                        _json(['code' => 199,'msg' => '启动kafka失败' . $_line], 1);
                                    } elseif (stripos($_line, 'insufficient memory') !== false) {
                                        _json(['code' => 199,'msg' => '启动kafka失败,内存不足'], 1);
                                    }
                                }
                                _json(['code' => 199,'msg' => '启动kafka失败,其他原因'], 1);
                            }
                        } else {
                            _json(['code' => 200,'msg' => '启动kafka成功'], 1);
                        }
                    } else {
                        _json(['code' => 199,'msg' => '启动kafka失败,请检查脚本可执行权限'], 1);
                    }
                } else {
                    _json(['code' => 198,'msg' => 'kafka已启动,请勿重复启动'], 1);
                }
                break;
            case 'spark':
                //检查启动脚本的可执行权限
                $spark_sh = ROOTPATH . 'envsoft/spark-2.4.7-bin-hadoop2.7/sbin/start-all.sh';
                if(is_executable($spark_sh)){
                    exec('sudo '.$spark_sh, $start_spark_result, $start_spark_status);
                    //dump($start_spark_result);
                    if(count($start_spark_result) > 0){
                        $_error = 0;
                        foreach($start_spark_result as $_line){
                            if (stripos($_line, 'Permission denied') !== false) {
                                if(stripos($_line,'password') !== false){
                                    $_error = 1;
                                    break;
                                } else {
                                    $_error = 2;
                                }
                            } elseif(stripos($_line, 'Stop it first') !== false){
                                _json(['code' => 199,'msg' => 'Spark已启动Master或Worker,请先停止再启动'], 1);
                            }
                        }
                        switch($_error){
                            case 1:
                                _json(['code' => 199,'msg' => '启动 Spark 失败,请配置ssh以免密方式启动,请参考文档2.1'], 1);
                                break;
                            case 2:
                                _json(['code' => 199,'msg' => '启动 Spark 失败,请检查apache或者nginx等webserver用户是否拥有sudo权限'], 1);
                                break;
                            default:
                                break;
                        }
                    }
                    _json(['code' => 200,'msg' => '启动Spark成功']);
                    
                } else {
                    _json(['code' => 198,'msg' => '启动 spark 失败,请检查 start-all.sh 脚本可执行权限'], 1);
                }
                break;
            default:
                _json(['code' => 198,'msg' => '启动软件参数错误']);
                break;
        } */
    }
}
