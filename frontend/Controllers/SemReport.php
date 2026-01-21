<?php 
namespace App\Controllers;

use App\Models\ConfigSemModel;
use CodeIgniter\CLI\Console;

class SemReport extends BaseController
{
    protected $advTypeMap = [
        'psConvert'=>'ocpc_ps_convert',
        'msConvert'=>'ocpc_ms_convert',
        'zsConvert'=>'ocpc_zs_convert'
    ];
    
    //聚合转化类型
    private $convertTypeTotal = [];
    
    public function __construct(){
        $this->convertTypeTotal = getTotalConvertType();
    }
    
    /**
     * 统一上报
     * 根据参数判断媒体渠道
     * 判断优先级    msclkid(bing) > gdt_vid(微信流量),qz_gdt(非微信) > bd_vid(百度) > qhclickid(360搜索) > sourceid(360展示)
     *
     */
    public function reportCenter(){
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            // 如果是 OPTIONS 请求，终止脚本执行 防止bing上报的重复数据
            exit();
        }
        $msclkid = $this->request->getGet('msclkid', 'trim|xss_clean|strip_tags', 'N');     //bing点击id
        
        $qz_gdt = $this->request->getGet('qz_gdt', 'trim|xss_clean|strip_tags', '');       //非微信流量点击id
        $gdt_vid = $this->request->getGet('gdt_vid', 'trim|xss_clean|strip_tags', '');     //微信流量点击id
        
        $bd_vid = $this->request->getGet('bd_vid', 'trim|xss_clean|strip_tags', '');        //百度点击id
        
        $qhclickid = $this->request->getGet('qhclickid', 'trim|xss_clean|strip_tags', '');  //360搜索广告点击id
        $sourceid = $this->request->getGet('sourceid', 'trim|xss_clean|strip_tags', '');      //360展示广告点击id
        
        $convert = $this->request->getGet('convert', 'trim|xss_clean|strip_tags|intval', ''); //转化类型
        
        if(!empty($msclkid) && ($msclkid !== 'N')){
            //Bing ocpc上报
            $convert = isset($this->convertTypeTotal[$convert]['bingCode']) ? $this->convertTypeTotal[$convert]['bingCode'] : '';
            if(empty($convert)){
                exit(json_encode(['code' => 199,'msg' => 'convert code error'], JSON_UNESCAPED_UNICODE));
            }
            $this->_reportBing($msclkid, $convert);
        }  elseif(!empty($qz_gdt) || !empty($gdt_vid)){
            $tx_click_id = $qz_gdt || $gdt_vid;
            //腾讯搜索 ocpc上报
            $convert = isset($this->convertTypeTotal[$convert]['txCode']) ? $this->convertTypeTotal[$convert]['txCode'] : '';
            if(empty($convert)){
                exit(json_encode(['code' => 199,'msg' => 'convert code error'], JSON_UNESCAPED_UNICODE));
            }
            $this->_reportTencent($tx_click_id, $convert);
        } elseif(!empty($bd_vid)){
            //百度ocpc上报
            $convert = isset($this->convertTypeTotal[$convert]['baiduCode']) ? $this->convertTypeTotal[$convert]['baiduCode'] : '';
            if(empty($convert)){
                exit(json_encode(['code' => 199,'msg' => 'convert code error'], JSON_UNESCAPED_UNICODE));
            }
            
            $this->_reportBaidu($bd_vid, $convert);
        } elseif(!empty($qhclickid)){
            $convert = isset($this->convertTypeTotal[$convert]['360Code']) ? $this->convertTypeTotal[$convert]['360Code'] : '';
            if(empty($convert)){
                exit(json_encode(['code' => 198,'msg' => 'convert code error'], JSON_UNESCAPED_UNICODE));
            }
            //360PC和移动搜索上报
            if(isMobileDevice()){
                $this->_report360('msConvert', $convert, $qhclickid);
            } else {
                $this->_report360('psConvert', $convert, $qhclickid);
            }
        } elseif(!empty($sourceid)){
            $convert = isset($this->convertTypeTotal[$convert]['360Code']) ? $this->convertTypeTotal[$convert]['360Code'] : '';
            if(empty($convert)){
                exit(json_encode(['code' => 197,'msg' => 'convert code error'], JSON_UNESCAPED_UNICODE));
            }
            //360展示推广上报
            $this->_report360('zsConvert', $convert, $sourceid);
        }
    }
    
    /**
     * 页面初始化收集的参数 - 激活Bing搜索UET标签
     */
    public function  reportInit(){
        $params = [];
        $params['Ver'] = $this->request->getGet('Ver', 'trim|intval', 2);                     //版本号 默认2
        $params['mid'] = $this->request->getGet('mid', 'trim|xss_clean|strip_tags', '');      //microsoft advertise id
        $params['sid'] = $this->request->getGet('sid', 'trim|xss_clean|strip_tags', '');      //session id 会话id
        $params['vid'] = $this->request->getGet('vid', 'trim|xss_clean|strip_tags', '');      //visitor id 访客id
        $params['vids'] = $this->request->getGet('vids', 'trim|intval', 0);
        $params['bo'] = $this->request->getGet('bo', 'trim|intval', 1);                       //请求计数 初始为1 随着请求自动累加
        $params['msclkid'] = $this->request->getGet('msclkid', 'trim|xss_clean|strip_tags', '');    //microsoft click id
        $params['uach'] = $this->request->getGet('uach', 'trim|xss_clean|strip_tags', '');     //跟系统版本有关的字符串
        $params['pi'] = $this->request->getGet('pi', 'trim|xss_clean|strip_tags', '');         //浏览器插件信息 hash串
        $params['lg'] = $this->request->getGet('lg', 'trim|xss_clean|strip_tags', '');         //浏览器语言
        $params['sw'] = $this->request->getGet('sw', 'trim|intval', 1920);                     //屏幕宽高 色彩位数
        $params['sh'] = $this->request->getGet('sh', 'trim|intval', 1080);
        $params['sc'] = $this->request->getGet('sc', 'trim|intval', 24);
        $params['tl'] = $this->request->getGet('tl', 'trim|xss_clean|strip_tags', '');              //页面title信息
        $params['kw'] = $this->request->getGet('kw', 'trim|xss_clean|strip_tags', '');              //页面keywords信息
        $params['p'] = $this->request->getGet('p', 'trim|xss_clean|strip_tags', '');                //页面url
        $params['r'] = $this->request->getGet('r', 'trim|xss_clean|strip_tags', '');                //页面referrer
        $params['lt'] = $this->request->getGet('lt', 'trim|intval', 100);                           //页面加载耗时 毫秒数
        $params['evt'] = $this->request->getGet('evt', 'trim|xss_clean|strip_tags', 'pageLoad');    //事件名称
        $params['sv'] = $this->request->getGet('sv', 'trim|intval', 1);                             //子版本号 默认1
        $params['cdb'] = $this->request->getGet('cdb', 'trim|xss_clean|strip_tags', 'AQAQ');        //用户隐私相关  默认AQAQ
        $params['rn'] = $this->request->getGet('rn', 'trim|intval', mt_rand(100000, 999999));                 //六位随机数 防止页面缓存
        
        //查找后台配置了 UET标签id才发送标签激活请求
        $config = model('ConfigSemModel')->getBingConfig();
        $configBing = array_column($config, null, 'conf_key');
        $uetid = $configBing['uetid']['conf_value'];
        if(!empty($uetid)){
            //发送UET标签激活请求
            $params['ti'] = $uetid;
            $uetUrl = 'https://bat.bing.com/action/0?'.http_build_query($params);
            $res = requestGet($uetUrl);
            if($res === true){
                exit(json_encode(['code'=>200,'msg'=>'ok']));
            } else {
                //出现上报错误
                exit(json_encode(['code'=>199,'msg'=>'fail']));
            }
        } else {
            //未填入uetid标签
            exit(json_encode(['code'=>201,'msg'=>'ok']));
        }
        
        /* $uetids = $configBing['uetid']['conf_value'];
         //多个uetid使用,号连接存放于表中
         $uetidArr= explode(',', $uetids);
         
         $resArr = [];
         if(count($uetidArr) > 0){
         foreach ($uetidArr as $uetid){
         if(!empty($uetid)){
         //发送UET标签激活请求
         $params['ti'] = $uetid;
         $uetUrl = 'https://bat.bing.com/action/0?'.http_build_query($params);
         $resArr[] = requestGet($uetUrl);
         }
         }
         } else {
         //未填入uetid标签
         exit(json_encode(['code'=>201,'msg'=>'ok']));
         }
         
         if(count($resArr) > 0){
         foreach ($resArr as $res){
         if($res !== true){
         //出现上报错误
         exit(json_encode(['code'=>199,'msg'=>'fail']));
         }
         }
         }
         exit(json_encode(['code'=>200,'msg'=>'ok'])); */
    }
    

    
    /**
     * 360 ocpc上报
     */
    private function _report360($advType, $convertType, $qhclickid){
        $_config360 = model('ConfigSemModel')->get360Config();
        $config360 = array_column($_config360, null, 'conf_key');
        //$convertType 如果是 submit或者order 需要传入trans_id参数 这里使用订单时间错的md5值作为trans_id
        $trans_id = $jzqs = 0;
        if(($advType == 'psConvert' || $advType == 'msConvert') && ($convertType == 'SUBMIT' || $convertType == 'ORDER')){
            $trans_id = md5(time());
        }
        if($advType == 'zsConvert'){
            $trans_id = md5(time());
            $jzqs = $config360['360jzqs']['conf_value'];
        }
        $data_detail = ['qhclickid'=>$qhclickid, 'event'=>$convertType, 'event_time'=>time()];
        if($trans_id > 0){
            $data_detail['trans_id'] = $trans_id;
        }
        if($jzqs > 0){
            $data_detail['jzqs'] = $jzqs;
        }
        $postdata_arr = [
            'data'=>[
                'request_time'=>time(),
                'data_industry'=>$this->advTypeMap[$advType],
                'data_detail'=>$data_detail
            ]
        ];
        $postdata_json = json_encode($postdata_arr);
        //dump($postdata_arr);
        //exit;
        
        $signstr =  $config360['360secret']['conf_value'].$postdata_json;
        $header = ['App-Key:'.$config360['360key']['conf_value'],'Content-Type: application/json','App-Sign:'.md5($signstr)];
        $reportResult = requestPost('https://convert.dop.360.cn/uploadWebConvert', $postdata_json, $header);
        file_put_contents(WRITEPATH.'/logs/semConvertReport360.log',json_encode($header).'-'.$postdata_json.'-'.$reportResult."\r\n", FILE_APPEND);
        $resultJson = json_decode($reportResult,true);
        if($resultJson['errno'] == 0){
            echo json_encode(['ok'=>200,'msg'=>'ok']);
        } else {
            echo json_encode(['ok'=>199,'msg'=>'fail']);
        }
    }
    
    
    /**
     * 百度ocpc上报
     */
    private function _reportBaidu($bd_vid, $convert){
        $config = model('ConfigSemModel')->getBaiduConfig();
        $configBaidu = array_column($config,null,'conf_key');
        $token = $configBaidu['baiduToken']['conf_value'];
        $loginUrl = $configBaidu['baiduLandpage']['conf_value'].'/?bd_vid='.$bd_vid;
        $conversionTypes = [
            [
                'logidUrl' => $loginUrl, // 您的落地页url
                'newType' => $convert  //转化类型
            ]
        ];
        $reqData = array('token' => $token, 'conversionTypes' => $conversionTypes);
        $reqData = json_encode($reqData);
        // 发送完整的请求数据
        // do some log
        //print_r('req data: ' . $reqData . "\n");
        //exit;
        // 向百度发送数据
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, 'https://ocpc.baidu.com/ocpcapi/api/uploadConvertData');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $reqData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8',
            'Content-Length: ' . strlen($reqData)
        )
            );
        // 添加重试，重试次数为3
        for ($i = 0; $i < 3 ; $i++) {
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            //dump($httpCode);
            if ($httpCode === 200) {
                // 打印返回结果
                // do some log
                //print_r('retry times: ' . $i . ' res: ' . $response . "\n");
                file_put_contents(WRITEPATH.'/logs/semConvertReportBaidu.log', $reqData.'-'.$response."\r\n", FILE_APPEND);
                $res = json_decode($response, true);
                // status为4，代表服务端异常，可添加重试
                $status = $res['header']['status'];
                if ($status !== 4) {
                    curl_close($ch);
                    //return $status === 0;
                    exit(json_encode(['ok'=>200,'msg'=>'ok']));
                }
            }
        }
        curl_close($ch);
        exit(json_encode(['ok'=>199,'msg'=>'fail']));
    }
    
    /**
     * Bing ocpc上报
     */
    private function _reportBing($msclkid, $convert){
        $params = [];
        $params['Ver'] = $this->request->getGet('Ver', 'trim|intval', 2);                     //版本号 默认2
        $params['mid'] = $this->request->getGet('mid', 'trim|xss_clean|strip_tags', '');      //microsoft advertise id
        $params['sid'] = $this->request->getGet('sid', 'trim|xss_clean|strip_tags', '');      //session id 会话id
        $params['vid'] = $this->request->getGet('vid', 'trim|xss_clean|strip_tags', '');      //visitor id 访客id
        $params['vids'] = $this->request->getGet('vids', 'trim|intval', 0);
        $params['bo'] = $this->request->getGet('bo', 'trim|intval', 1);                       //请求计数 初始为1 随着请求自动累加
        $params['en'] = 'Y';
        $params['p'] = $this->request->getGet('p', 'trim|xss_clean|strip_tags', '');           //页面url
        $params['sw'] = $this->request->getGet('sw', 'trim|intval', 1920);                     //屏幕宽高 色彩位数
        $params['sh'] = $this->request->getGet('sh', 'trim|intval', 1080);
        $params['sc'] = $this->request->getGet('sc', 'trim|intval', 24);
        $params['evt'] = 'custom';
        $params['cdb'] = $this->request->getGet('cdb', 'trim|xss_clean|strip_tags', 'AQAQ');        //用户隐私相关  默认AQAQ
        $params['rn'] = $this->request->getGet('rn', 'trim|intval', mt_rand(100000, 999999));                 //六位随机数 防止页面缓存
        $params['msclkid'] = $msclkid;
        $params['ea'] = $convert;
        $el = $this->request->getGet('el', 'trim|xss_clean|strip_tags', '');
        if(!empty($el)){
            $params['el'] = $el;
        }
        //查找后台配置了 UET标签id才发送标签激活请求
        $config = model('ConfigSemModel')->getBingConfig();
        $configBing = array_column($config, null, 'conf_key');
        $uetid = $configBing['uetid']['conf_value'];
        if(!empty($uetid)){
            //发送UET标签激活请求
            $params['ti'] = $uetid;
            $uetUrl = 'https://bat.bing.com/action/0?'.http_build_query($params);
            $res = requestGet($uetUrl);
            //echo $uetUrl;
            file_put_contents(WRITEPATH.'/logs/semConvertReportBing.log', $uetUrl.'-'.$res."\r\n", FILE_APPEND);
            if($res === true){
                exit(json_encode(['code'=>200,'msg'=>'ok']));
            } else {
                //出现上报错误
                exit(json_encode(['code'=>199,'msg'=>'fail']));
            }
        } else {
            //未填入uetid标签
            exit(json_encode(['code'=>201,'msg'=>'ok']));
        }
    }
    
    /**
     * 腾讯搜索  ocpc上报
     */
    private function _reportTencent($tx_click_id, $convert){
        $params = [];
        
        //查找后台配置了 UET标签id才发送标签激活请求
        $config = model('ConfigSemModel')->getTxConfig();
        $configBing = array_column($config, null, 'conf_key');
        $txAccessToken = $configBing['txAccessToken']['conf_value'];
        $txAccountId = $configBing['txAccountId']['conf_value'];
        $txUserActionSetId = $configBing['txUserActionSetId']['conf_value'];
        $timestamp = time();
        $nonce = $this->getNonce();
        
        $api = "https://api.e.qq.com/v1.3/user_actions/add?access_token=".$txAccessToken."&timestamp=".$timestamp."&nonce=".$nonce;
        $header = [
            'Content-Type: application/json',
        ];
        $bodyArr = [
            'account_id'=>$txAccountId,
            'user_action_set_id'=>$txUserActionSetId,
            'actions'=>[
                'action_time'=>$timestamp,
                'action_type'=>$convert,
                'trace'=>['click_id'=>$tx_click_id],
                'url'=>$_SERVER['HTTP_REFERER'],            //获取发送请求时的H5 url地址
                'channel'=>'TENCENT'
            ]
        ];
        $bodyJson = json_encode($bodyArr, JSON_UNESCAPED_UNICODE);
        $res = requestPost($api, $bodyJson, $header);
        file_put_contents(WRITEPATH.'/logs/semConvertReportTencent.log', $api.'-'.$bodyJson.'-'.$header.'-'.$res."\r\n", FILE_APPEND);
        if($res === true){
            exit(json_encode(['code'=>200,'msg'=>'ok']));
        } else {
            //出现上报错误
            exit(json_encode(['code'=>199,'msg'=>'fail']));
        }
    }
    
    /**
     * 生成用于请求腾讯接口的nonce
     * @param number $length
     * @return string
     */
    private function getNonce($length = 32) {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $nonce = '';
        $charLen = strlen($characters);
        
        for ($i = 0; $i < $length; $i++) {
            $nonce .= $characters[random_int(0, $charLen - 1)];
        }
        return $nonce;
    }
    
    
    /**
     * 单独上报接口 deprecated
     * 推荐使用统一上报接口
     * 360 ocpc上报接口
     * TODO 接口参数校验签名
     */
    public function report360(){
        // 获取表单数据
        $qhclickid = addslashes(trim($_GET['qhclickid']));
        if(!empty($qhclickid)){
            //上报ocpc数据
            $advType = addslashes(trim($_GET['adv']));
            $convertType = addslashes(trim($_GET['convert']));
            if(!in_array($advType, array_keys($this->advTypeMap))){
                exit(json_encode(['ok'=>199,'msg'=>'fail']));
            }
            
            $this->_report360($advType, $convertType, $qhclickid);
        } else {
            //记录日志
            echo json_encode(['ok'=>-3,'msg'=>'qhclickid error']);
        }
    }
    
    /**
     * 单独上报接口 deprecated
     * 推荐使用统一上报接口
     * 百度 ocpc上报接口
     */
    public function reportBaidu(){
        /* $bd_vid = $this->request->getGet('bd_vid', 'trim|xss_clean|strip_tags', '');
        $convert = $this->request->getGet('convert', 'trim|xss_clean|strip_tags', '');
        if (empty($bd_vid) || empty($convert)) {
            //校验失败
            exit(json_encode(['code' => 199,'msg' => 'fail'], JSON_UNESCAPED_UNICODE));
        } */
    }
}