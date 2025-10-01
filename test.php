<?php
//360
// $username = 'fm45728@21cn.com';
// $md5 = md5('Ad12-124.');
// $key = 'f44a715d56fb436b31dd1470d74798f1';
// $secret = '6e69334f7ad5d7ea95947f178688b8fe';
// //$key = '888888888888PDOException8888888888888888888';
// //echo AesEncrypt($md5 , $key, substr($key, 16, 16));
// $iv = substr($secret, 16, 16);
// $encrypt = openssl_encrypt($md5, 'AES-128-CBC', $secret, OPENSSL_RAW_DATA, $iv);
// $data = strtolower(bin2hex($encrypt));
// $encryptedPwd =  substr($data, 0, 64);

// //发送POST请求token
// $api = 'https://api.e.360.cn/uc/account/clientLogin';
// $body = 'username='.$username.'&passwd='.$encryptedPwd;

// $header = ['Content-Type: application/x-www-form-urlencoded;charset=utf-8','apiKey:'.$key];
// $res= requestPost($api, $body, $header);
// var_dump($res);
//baidu
$json = '{"appId":"fff713dab28b4a31ae5eea275cce6f9b","authCode":"eyJhbGciOiJIUzM4NCJ9.eyJhdWQiOiLmibPmiYvmlbDmja4iLCJzdWIiOiJleGMiLCJ1aWQiOjY2MDkyODYzLCJhcHBJZCI6ImZmZjcxM2RhYjI4YjRhMzFhZTVlZWEyNzVjY2U2ZjliIiwiaXNzIjoi5ZWG5Lia5byA5Y-R6ICF5Lit5b-DIiwicGxhdGZvcm1JZCI6IjQ5NjAzNDU5NjU5NTg1NjE3OTQiLCJleHAiOjE3NDk3MjMxNTUsImp0aSI6Ii05MDg4MDQxMzYyNTU4MTU2Nzc0In0.6OrHUgqw5zF6Kn5TOHy8TuEtUv-OB9X8zpgS_qfF4q6sxurHl4kRXLMhSN6BN96U","state":"9eeac159c8b210bcf3355e9955b72fc6","timestamp":"1749721355584","userId":"66092863"}';

$iv = str_repeat("\0", 16);
$secret = '2090057c1c7aee51b30d1baaf0820676';
$bytes = base64_encode($json);
$secret = substr($secret, 0, 16);
//$secret = substr(hash('sha256', $secret, true), 0, 16);

$blockSize = 16;
$padding = $blockSize - (strlen($bytes) % $blockSize);
$bytes .= str_repeat("\0", $padding);

$encrypt = openssl_encrypt($bytes, 'AES-128-CBC', $secret, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);
$data = strtoupper(bin2Hex($encrypt));
echo $data;

//echo AESUtils::encryptAES($bytes, $secret, $iv);


function requestPost($url='', $data = array(), $header = array(),$timeout=6,$count=3,$response_header=0){
    static $index = 0 ;
    $index++;
    if(empty($url) || (strpos($url, 'http') === false)){
        throw new exception('缺少url参数或者url格式不合法,url应包含http或者https协议');
        exit();
    } else {
        //如果是https协议的url,检查openssl组件
        //用检测函数存在的方式进行检查 检测组件是否加载的方式不一定准确
        //有些组件被直接编译进了php,并不一定是通过加载组件的方式进行加载的，比如说 openssl
        if(strpos($url, 'https') !== false){
            if(!function_exists('openssl_open')){
                throw new exception('缺少openssl组件支持,请确保openssl扩展已经正确加载或已经编译进php');
                exit();
            }
        }
    }
    $ch = null;
    if(function_exists('curl_init')){
        $ch = curl_init();
    }
    if($ch){
        //初始化curl
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE); //不验证 https 证书
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,FALSE);
        curl_setopt($ch, CURLOPT_HEADER, $response_header);    //是否返回响应头信息 true 返回 false不返回
        if(!empty($header)){
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
        
        //curl_setopt($ch, CURLOPT_PROXY, '192.168.2.108'); //代理服务器地址
        //curl_setopt($ch, CURLOPT_PROXYPORT,'8888'); 		//代理服务器端口
        //curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip'); //处理返回content-type:gzip的乱码
        curl_setopt($ch, CURLOPT_POST, 1);				// post方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);	// post数据 php数组格式或字符串
        $content = curl_exec($ch);
        if($content === false){
            if(curl_errno($ch) == CURLE_OPERATION_TIMEDOUT){
                if($index < $count){
                    //请求重发
                    requestPost($url,$data);
                }
            }
            
        }
        if($response_header){
            $response_header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            curl_close($ch);
            $response_header = substr($content,0,$response_header_size);
            $header_arr = explode("\r\n", $response_header);
            //print_r($header_arr);
            foreach ($header_arr as $v){
                if(stripos($v, 'set-cookie') !== false){
                    $_temp = explode(':', $v);
                    unset($_temp[0]);                          //2022-12-13 修复cookie中包含时间格式 造成读取数据不全的bug
                    $_cookies_arr[] = trim(implode('', $_temp));
                }
            }
            //$response_cookie = implode("\r\n\r\n", $_cookies_arr);
            $response_cookie = $_cookies_arr;
            //preg_match('/^Set-Cookie: (.*?);/m',$response_header,$m);
            //print_r($response_header);
            $response_body = substr($content,$response_header_size);
            return array('response_header'=>$response_header,'response_body'=>$response_body,'response_cookie'=>$response_cookie);
        } else {
            return $content;
        }
    } else {
        //检查 allow_url_fopen 配置
        //开启返回 "1" 关闭返回 ""
        //allow_url_fopen的修改范围是PHP_INI_SYSTEM，这个选项只能在php.ini或httpd.conf中修改，不能在脚本中修改
        if(ini_get('allow_url_fopen') == ''){
            //ini_set('allow_url_fopen', '1');
            throw new Exception('请检查allow_url_fopen配置项是否在php.ini中开启');
            exit();
        }
        $data = http_build_query($data);
        $context = array(
            'http'=>array(
                'method'=>'POST',
                'content'=>$data
            )
        );
        $context  = stream_context_create($context);
        $content = file_get_contents($url,false,$context);
        return $content;
    }
}




class AESUtils {
    private static $providerInitialized = false;
    
    static function __constructStatic() {
        self::initializeProvider();
    }
    
    private static function initializeProvider() {
        if (!self::$providerInitialized) {
            // PHP uses OpenSSL which is typically built-in
            // Equivalent to Security.addProvider(new BouncyCastleProvider())
            if (!extension_loaded('openssl')) {
                throw new Exception('OpenSSL extension is required');
            }
            self::$providerInitialized = true;
        }
    }
    
    /**
     * 将字符串 keyString 的AES秘钥转换成SecretKey对象
     *
     * @param string $keyString
     * @return string
     * @throws Exception
     */
    public static function loadKeyAES($keyString) {
        // Convert string to bytes using UTF-8 encoding (equivalent to StandardCharsets.UTF_8)
        return $keyString;
    }
    
    /**
     * 加密
     *
     * @param string $source (byte array equivalent)
     * @param string $key (SecretKey equivalent)
     * @param string $vectorKey
     * @return string
     * @throws Exception
     */
    public static function encryptAES($source, $key, $vectorKey) {
        self::initializeProvider();
        
        // Create IV from vectorKey bytes (equivalent to IvParameterSpec)
        $iv = substr($vectorKey, 0, 16);
        if (strlen($iv) < 16) {
            $iv = str_pad($iv, 16, "\0");
        }
        
        $dataLength = strlen($source);
        if ($dataLength % 16 != 0) {
            $dataLength = $dataLength + (16 - ($dataLength % 16));
        }
        
        // Manual padding (equivalent to System.arraycopy)
        $paddingBytes = str_pad($source, $dataLength, "\0");
        
        // AES/CBC/NoPadding encryption
        $encrypted = openssl_encrypt($paddingBytes, 'AES-128-CBC', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);
        if ($encrypted === false) {
            throw new Exception("AES encryption failed: " . openssl_error_string());
        }
        
        return self::bin2HexStr($encrypted);
    }
    
    /**
     * 加密
     *
     * @param string $source (byte array equivalent)
     * @param string $key (SecretKey equivalent)
     * @return string
     * @throws Exception
     */
    public static function encryptAESWithoutVector($source, $key) {
        self::initializeProvider();
        
        $dataLength = strlen($source);
        if ($dataLength % 16 != 0) {
            $dataLength = $dataLength + (16 - ($dataLength % 16));
        }
        
        // Manual padding (equivalent to System.arraycopy)
        $paddingBytes = str_pad($source, $dataLength, "\0");
        
        // AES/ECB/NoPadding encryption
        $encrypted = openssl_encrypt($paddingBytes, 'AES-128-ECB', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING);
        if ($encrypted === false) {
            throw new Exception("AES encryption failed: " . openssl_error_string());
        }
        
        // Equivalent to Base64.getEncoder().encodeToString()
        return base64_encode($encrypted);
    }
    
    /**
     * 解密
     *
     * @param string $encrypedStr
     * @param string $key (SecretKey equivalent)
     * @return string
     * @throws Exception
     */
    public static function decryptAES($encrypedStr, $key) {
        self::initializeProvider();
        
        // Equivalent to Base64.getDecoder().decode()
        $source = base64_decode($encrypedStr);
        if ($source === false) {
            throw new Exception("Base64 decode failed");
        }
        
        // AES/ECB/NoPadding decryption
        $decrypted = openssl_decrypt($source, 'AES-128-ECB', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING);
        if ($decrypted === false) {
            throw new Exception("AES decryption failed: " . openssl_error_string());
        }
        
        // Return as UTF-8 string (equivalent to StandardCharsets.UTF_8)
        return $decrypted;
    }
    
    /**
     * 解密
     *
     * @param string $encrypedStr
     * @param string $key (SecretKey equivalent)
     * @param string $vectorKey
     * @return string
     * @throws Exception
     */
    public static function decryptAESWithVector($encrypedStr, $key, $vectorKey) {
        self::initializeProvider();
        
        // Create IV from vectorKey bytes
        $iv = substr($vectorKey, 0, 16);
        if (strlen($iv) < 16) {
            $iv = str_pad($iv, 16, "\0");
        }
        
        $source = self::hexStringToByte($encrypedStr);
        
        // AES/CBC/NoPadding decryption
        $decrypted = openssl_decrypt($source, 'AES-128-CBC', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);
        if ($decrypted === false) {
            throw new Exception("AES decryption failed: " . openssl_error_string());
        }
        
        // Return as UTF-8 string (equivalent to StandardCharsets.UTF_8)
        return $decrypted;
    }
    
    /**
     * @param string $bytes
     * @return string 将二进制数组转换为十六进制字符串  2-16
     */
    public static function bin2HexStr($bytes) {
        $hexStr = "0123456789ABCDEF";
        $result = "";
        
        for ($i = 0; $i < strlen($bytes); $i++) {
            $byte = ord($bytes[$i]);
            // 字节高4位
            $hex = $hexStr[($byte & 0xF0) >> 4];
            // 字节低4位
            $hex .= $hexStr[$byte & 0x0F];
            $result .= $hex;
        }
        
        return $result;
    }
    
    private static function parse($c) {
        $charCode = ord($c);
        if ($charCode >= ord('a')) {
            return ($charCode - ord('a') + 10) & 0x0f;
        }
        if ($charCode >= ord('A')) {
            return ($charCode - ord('A') + 10) & 0x0f;
        }
        return ($charCode - ord('0')) & 0x0f;
    }
    
    public static function hexStringToByte($hex) {
        $length = strlen($hex) / 2;
        $result = "";
        $j = 0;
        
        for ($i = 0; $i < $length; $i++) {
            $c0 = $hex[$j++];
            $c1 = $hex[$j++];
            $byte = (self::parse($c0) << 4) | self::parse($c1);
            $result .= chr($byte);
        }
        
        return $result;
    }
}

// Static initialization equivalent
AESUtils::__constructStatic();

