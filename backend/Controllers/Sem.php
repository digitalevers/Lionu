<?php
/**
 * SEM投放模块
 */
namespace App\Controllers;



class Sem extends NeedloginController
{
    //360 PC搜索转化类型定义
    private $psConvertType360 = [
        'SUBMIT'=>'表单提交',
        'CALL'=>'有效电话拨打',
        'ADVISORY'=>'一句话咨询',
        'SITEDOWNLOAD'=>'下载按钮点击',
        'SUBMIT_BUTTON'=>'表单按钮点击',
        'ADVISORY_BUTTON'=>'咨询按钮点击',
        'CALL_BUTTON'=>'电话按钮点击',
        'SHOP_BUTTON'=>'购买按钮点击',
        'CART_BUTTON'=>'加入购物车按钮点击',
        'ORDER'=>'订单',
        'REGISTERED'=>'注册',
        'ROLE_CREAT'=>'创建角色',
        'SITE_VISIT_DEPTH'=>'深度页面访问',
        'COUSTOMIZE'=>'客户自定义',
        'MIDDLE_PAGE'=>'中间页',
        'REGISTER_BUTTON'=>'注册按钮点击',
        'BROWSE_DEPTH'=>'有效浏览',
        'BROWSETIME'=>'浏览时长',
        'SCAN_BUTTON'=>'扫码点击',
        'ADVISORY_DEPTH'=>'三句话咨询',
        'LOW_PAY'=>'低价付费',
        'ADD_FANS_WX'=>'微信加粉',
        'PAY'=>'付费',
        'SCAN_CODE'=>'扫码',
        'APPLET_STARTUP'=>'小程序调起',
        'LOGIN'=>'登录',
        'ADD_TO_CART'=>'加购物车',
        'VPPV'=>'VPPV页面深度访问',
        'INTENTIONAL'=>'有意向',
        'REAL_NAME'=>'实名',
        'RETENTION'=>'次留',
        'PLACE_ORDER'=>'订单提交',
        'EFFECTIVE_ADVISORY'=>'有效咨询',
        'ORDER_VALIDITY'=>'订单有效性',
        'ACTIVATION'=>'激活',
        'DETAILS_PAGE_ARRIVED'=>'详情页到达',
        'PAY_SUCCESS'=>'支付成功',
        'CREDIT'=>'授信',
        'WX_BUTTON_C'=>'微信复制按钮点击',
        'CONCEM'=>'关注',
        'LEAVE_CONTACT'=>'留联',
        'RELEASE'=>'发布',
        'TRY_TO_PLAY'=>'试玩',
        'SUBMIT_RESUME'=>'投递简历',
        'ENTERPRISE_CERTIFICATION'=>'企业认证',
        'VISIT_CLINIC'=>'到诊'
    ];
    
    //360 移动搜索转化类型定义
    private $msConvertType360 = [
        'SUBMIT'=>'表单提交',
        'CALL'=>'有效电话拨打',
        'ADVISORY'=>'一句话咨询',
        'SITEDOWNLOAD'=>'下载按钮点击',
        'SUBMIT_BUTTON'=>'表单按钮点击',
        'ADVISORY_BUTTON'=>'咨询按钮点击',
        'CALL_BUTTON'=>'电话按钮点击',
        'SHOP_BUTTON'=>'购买按钮点击',
        'CART_BUTTON'=>'加入购物车按钮点击',
        'ORDER'=>'订单',
        'REGISTERED'=>'注册',
        'ROLE_CREAT'=>'创建角色',
        'SITE_VISIT_DEPTH'=>'深度页面访问',
        'APP_DOWNLOAD'=>'APP下载',
        'APP_ACTIVATION'=>'APP激活',
        'APP_RETENTION'=>'APP次留',
        'APP_PAY'=>'APP付费',
        'COUSTOMIZE'=>'客户自定义',
        'MIDDLE_PAGE'=>'中间页',
        'REGISTER_BUTTON'=>'注册按钮点击',
        'BROWSE_DEPTH'=>'有效浏览',
        'BROWSETIME'=>'浏览时长',
        'SCAN_BUTTON'=>'扫码点击',
        'ADVISORY_DEPTH'=>'三句话咨询',
        'LOW_PAY'=>'低价付费',
        'ADD_FANS_WX'=>'微信加粉',
        'PAY'=>'付费',
        'SCAN_CODE'=>'扫码',
        'APPLET_STARTUP'=>'小程序调起',
        'LOGIN'=>'登录',
        'ADD_TO_CART'=>'加购物车',
        'VPPV'=>'VPPV页面深度访问',
        'INTENTIONAL'=>'有意向',
        'REAL_NAME'=>'实名',
        'PLACE_ORDER'=>'订单提交',
        'EFFECTIVE_ADVISORY'=>'有效咨询',
        'ORDER_VALIDITY'=>'订单有效性',
        'DETAILS_PAGE_ARRIVED'=>'详情页到达',
        'PAY_SUCCESS'=>'支付成功',
        'STARTUP_APP'=>'调起 APP',
        'CREDIT'=>'授信',
        'WX_BUTTON_C'=>'微信复制按钮点击',
        'CONCEM'=>'关注',
        'LEAVE_CONTACT'=>'留联',
        'RELEASE'=>'发布',
        'TRY_TO_PLAY'=>'试玩',
        'SUBMIT_RESUME'=>'投递简历',
        'ENTERPRISE_CERTIFICATION'=>'企业认证',
        'VISIT_CLINIC'=>'到诊'
    ];
    //360 展示广告转化类型定义
    private $zsConvertType360 = [
        'SUBMIT'=>'表单提交',
        'CALL'=>'有效电话拨打',
        'ADVISORY'=>'一句话咨询',
        'SITEDOWNLOAD'=>'下载按钮点击',
        'SUBMIT_BUTTON'=>'表单按钮点击',
        'ADVISORY_BUTTON'=>'咨询按钮点击',
        'CALL_BUTTON'=>'电话按钮点击',
        'SHOP_BUTTON'=>'购买按钮点击',
        'ORDER'=>'订单转化',
        'REGISTERED'=>'注册转化',
        'ROLE_CREAT'=>'创建角色',
        'SITE_VISIT_DEPTH'=>'深度页面访问',
        'COUSTOMIZE'=>'客户自定义',
        'MIDDLE_PAGE'=>'中间页',
        'REGISTER_BUTTON'=>'注册按钮点击',
        'BROWSE_DEPTH'=>'有效浏览',
        'BROWSETIME'=>'浏览时长',
        'SCAN_BUTTON'=>'扫码点击',
        'ADVISORY_DEPTH'=>'三句话咨询',
        'LOW_PAY'=>'低价付费',
        'ADD_FANS_WX'=>'微信加粉',
        'PAY'=>'付费',
        'SCAN_CODE'=>'扫码',
        'APPLET_STARTUP'=>'小程序调起',
        'LOGIN'=>'登录',
        'ADD_TO_CART'=>'加购物车',
        'VPPV'=>'VPPV页面深度访问',
        'INTENTIONAL'=>'有意向',
        'REAL_NAME'=>'实名',
        'RETENTION'=>'次留',
        'PLACE_ORDER'=>'订单提交',
        'EFFECTIVE_ADVISORY'=>'有效咨询',
        'DETAILS_PAGE_ARRIVED'=>'详情页到达',
        'PAY_SUCCESS'=>'支付成功',
        'CREDIT'=>'授信',
        'WX_BUTTON_C'=>'微信复制按钮点击',
        'CONCEM'=>'关注',
        'LEAVE_CONTACT'=>'留联',
        'RELEASE'=>'发布',
        'TRY_TO_PLAY'=>'试玩',
        'SUBMIT_RESUME'=>'投递简历',
        'ENTERPRISE_CERTIFICATION'=>'企业认证',
        'VISIT_CLINIC'=>'到诊',
        'APPLET_PAY'=>'小程序内付费',
        'APPLET_ROLE_CREAT'=>'小程序内创角'
    ];
    
    //baidu 转换类型
    private $convertTypeBaidu = [
            1 => '咨询按钮点击',
            2 => '电话按钮点击',
            3 => '表单提交成功',
            4 => '激活',
            5 => '表单按钮点击',
            6 => '下载（预约）按钮点击',
            7 => '购买按钮点击',
            8 => '短信咨询按钮点击',
            10 => '服务购买成功',
            14 => '订单提交成功',
            17 => '三句话咨询',
            18 => '留线索',
            20 => '关键页面浏览',
            25 => '注册',
            26 => '付费',
            27 => '客户自定义',
            28 => '次日留存',
            29 => '7日留存',
            30 => '电话拨通',
            32 => 'QQ咨询按钮点击',
            35 => '微信复制按钮点击',
            41 => '申请',
            42 => '授信',
            45 => '商品下单成功',
            46 => '加入购物车',
            47 => '商品收藏',
            48 => '商品详情页到达',
            49 => '登录（注册激活后登录）',
            50 => '预约',
            52 => '深度使用',
            56 => '到店',
            57 => '店铺调起',
            61 => '二次跳转',
            67 => '微信调起按钮点击',
            68 => '粉丝关注成功',
            71 => '应用调起',
            72 => '聊到相关业务',
            73 => '回访-电话接通',
            74 => '回访-信息确认',
            75 => '回访-发现意向',
            76 => '回访-高潜成交',
            77 => '回访-成单客户',
            78 => '店铺停留',
            79 => '微信加粉成功',
            89 => '放款',
            90 => '商品支付成功',
            92 => '有效咨询',
            93 => '付费阅读',
            94 => '进入书城并阅读',
            95 => '3日留存',
            96 => '4日留存',
            97 => '5日留存',
            98 => '6日留存',
            100 => '14日留存',
            112 => '微信小程序调起',
            115 => '添加至桌面',
            116 => '百度小程序调起',
            117 => '进件',
            118 => '付费观剧',
            119 => '关键行为'
    ];
    
    //聚合转化类型
    private $convertTypeTotal = [];
    
    public function __construct(){
        $this->convertTypeTotal = getTotalConvertType();
    }
    
    /**
     * 获取360key
     */
    public function get360Key(){
        if(trim($this->username) != 'admin'){
            exit(json_encode([
                'code' => 200,
                'msg' => 'ok',
                'data' => []
            ], JSON_UNESCAPED_UNICODE));
        }
        $db = \Config\Database::connect();
        $res = $db->query("SELECT `conf_key`,`conf_value` FROM u_conf_sem WHERE `conf_key` IN(?,?,?,?)", ['360key','360secret','360landpage','360jzqs'])->getResultArray();
        echo json_encode([
            'code' => 200,
            'msg' => 'ok',
            'data' => $res
        ], JSON_UNESCAPED_UNICODE);
    }
   
    /**
     * 生成360 ocpc上报联调代码
     */
    public function create360ReportCode(){
        $advType = trim($this->request->getPost('advType', FILTER_SANITIZE_MAGIC_QUOTES));
        $convertType = trim($this->request->getPost('convertType', FILTER_SANITIZE_MAGIC_QUOTES));
        $key = trim($this->request->getPost('key', FILTER_SANITIZE_MAGIC_QUOTES));
        $secret = trim($this->request->getPost('secret', FILTER_SANITIZE_MAGIC_QUOTES));
        $landpage = trim($this->request->getPost('landpage', FILTER_SANITIZE_MAGIC_QUOTES));
        $jzqs = $this->request->getPost('jzqs', FILTER_SANITIZE_MAGIC_QUOTES);
        
        if (! $this->validate([
            'key' => ['required'],
            'secret' => ['required'],
            'landpage' => ['required', 'valid_url']
        ])) {
            //校验失败
            exit(json_encode(['code' => 199,'msg' => 'fail'], JSON_UNESCAPED_UNICODE));
        }
        if(!array_key_exists($convertType, $this->{$advType . 'Type360'})){
            exit(json_encode(['code' => 198,'msg' => 'fail'], JSON_UNESCAPED_UNICODE));
        }
        if($advType == 'zsConvert' && empty($jzqs)){
            exit(json_encode(['code' => 197,'msg' => 'jzqs empty'], JSON_UNESCAPED_UNICODE));
        }
        
        //生成联调js代码 分三种情况 1。可以在前端标签上调用的js函数   2.由用户定义调用js函数的场景 页面初始化完成后或者请求接口的回调函数中   3.在后端调用的场景 比如次留
        $type_1 = ['SUBMIT','SITEDOWNLOAD','SUBMIT_BUTTON','ADVISORY_BUTTON','CALL_BUTTON','SHOP_BUTTON','CART_BUTTON','APP_DOWNLOAD','REGISTER_BUTTON','SCAN_BUTTON','APPLET_STARTUP','ADD_TO_CART','PLACE_ORDER','STARTUP_APP'];
        $type_2 = ['ORDER','REGISTERED','ROLE_CREAT','SITE_VISIT_DEPTH','MIDDLE_PAGE','BROWSE_DEPTH','SCAN_CODE','LOGIN','VPPV','DETAILS_PAGE_ARRIVED','WX_BUTTON_C','CONCEM','LEAVE_CONTACT'];
        $type_3 = ['CALL','ADVISORY','APP_ACTIVATION','APP_RETENTION','APP_PAY','COUSTOMIZE','BROWSETIME','ADVISORY_DEPTH','LOW_PAY','ADD_FANS_WX','PAY','INTENTIONAL','REAL_NAME','RETENTION','EFFECTIVE_ADVISORY','ORDER_VALIDITY','ACTIVATION','PAY_SUCCESS','CREDIT','LEAVE_CONTACT','RELEASE','TRY_TO_PLAY','SUBMIT_RESUME','ENTERPRISE_CERTIFICATION','VISIT_CLINIC','APPLET_PAY','APPLET_ROLE_CREAT'];
        if(in_array($convertType, $type_1)){
            $js = $this->get360ReportJsCode(1, $convertType, $advType);
        } else if(in_array($convertType, $type_2)){
            $js = $this->get360ReportJsCode(2, $convertType, $advType);
        } else {
            $js = $this->get360ReportJsCode(3, $convertType, $advType);
        }
        //key、secret等信息写入数据库
        if(trim($this->username) == 'admin'){
            $db = \Config\Database::connect();
            if(!empty($jzqs)){
                $updateResult = $db->query("UPDATE u_conf_sem SET `conf_value`= CASE `conf_key`
                                            WHEN '360key' THEN ?
                                            WHEN '360secret' THEN ?
                                            WHEN '360landpage' THEN ?
                                            WHEN '360jzqs' THEN ?
                                        END WHERE `conf_key` IN('360key','360secret','360landpage','360jzqs')", [$key, $secret, $landpage, $jzqs]);
            } else {
                $updateResult = $db->query("UPDATE u_conf_sem SET `conf_value`= CASE `conf_key`
                                            WHEN '360key' THEN ?
                                            WHEN '360secret' THEN ?
                                            WHEN '360landpage' THEN ?
                                        END WHERE `conf_key` IN('360key','360secret','360landpage')", [$key, $secret, $landpage]);
            }
        }
        exit(json_encode(['code' => 200,'msg' => 'ok', 'data'=>$js], JSON_UNESCAPED_UNICODE));
    }
    
    
    
    /**
     * 获取360转换类型
     */
    public function get360ConvertTypes(){
        $advType = $this->request->getPost('advType', FILTER_SANITIZE_MAGIC_QUOTES);
        $data = $this->{$advType . 'Type360'};
        exit(json_encode(['code' => 200,'msg' => 'ok','data'=>$data], JSON_UNESCAPED_UNICODE));
    }
    

    /**
     * 组装 params 参数
     */
//     private function assembleParams(){
        
//     }
    
    /**
     * 向前端返送360上报 js代码
     */
    private function get360ReportJsCode($scene, $convertType, $advType){
        //获取接口域名
        $domain = model('ConfigModel')->getDomain();
        if($scene == 1){
            //适合某些点击控件进行上报的场景
            return "<!-- 1.将下列代码放在联调URL页面的底部，置于</body>标签之上 。如果其他监测事件已经添加过下列代码，则无需重复添加 -->
<script type='text/javascript'>
    (function() {
      document.addEventListener('DOMContentLoaded', function() {
        function reportParams(button) {
          const urlParams = new URLSearchParams(window.location.search);
          const params = Object.fromEntries(urlParams.entries());
          if (params.qhclickid) {
            //console.log(params.qhclickid);
            const convertValue = button.getAttribute('data-convert');
            const requestUrl = 'https://".$domain."/SemReport/report360?method=ocpc&convert=' + convertValue + '&adv=".$advType."&' + new URLSearchParams(params).toString();
            fetch(requestUrl, {
              method: 'GET',
              headers: {
                'Content-Type': 'application/json'
              }
            }).then(response => {
              if (response.ok) {
                return response.json();
              }
              throw new Error('Network response was not ok.');
            }).then(data => {
              //console.log(data);
            }).catch(error => {
              console.error('There has been a problem with your fetch operation:', error);
            });
          }
        }
        // 获取具有 lionu 类名的按钮
        const lionuButtons = document.querySelectorAll('.lionu');
        if (lionuButtons) {
            //console.log(lionuButtons)
            // 添加点击事件监听器
            lionuButtons.forEach(function(button) {
              button.addEventListener('click', function() {
                reportParams(this);
              });
            });
        }
      })
    })();
</script>
<!-- 
    2.在需要上报事件的控件上（通常为button按钮或者a标签）增加 css样式类: lionu，并添加 data-convert属性
    例：<button class='... lionu' data-convert='".$convertType."'  ...>按钮</button> 
           或者 <a class='... lionu' data-convert='".$convertType."'  ...>a标签</a> 
           或者 <div class='... lionu' data-convert='".$convertType."'  ...>其他按钮</div> 
-->";
        } elseif($scene == 2){
            //适合某些需要主动调用js函数的地方 比如注册登录支付成功的回调中
            return "<!-- 1.将下列代码放在联调URL页面的底部，置于</body>标签之上 .如果其他监测事件已经添加过下列代码，则无需重复添加-->
<script type='text/javascript'>
    val params = {}
    function lionuReportParams() {
      const urlParams = new URLSearchParams(window.location.search);
      const params = Object.fromEntries(urlParams.entries());
      if (params.qhclickid) {
        //console.log(params.qhclickid);
        const requestUrl = 'https://".$domain."/SemReport/report360?method=ocpc&convert=".$convertType."&adv=".$advType."&' + new URLSearchParams(params).toString();
        fetch(requestUrl, {
          method: 'GET',
          headers: {
            'Content-Type': 'application/json'
          }
        }).then(response => {
          if (response.ok) {
            return response.json();
          }
          throw new Error('Network response was not ok.');
        }).then(data => {
          //console.log(data);
        }).catch(error => {
          console.error('There has been a problem with your fetch operation:', error);
        });
      }
    }
</script>
<!-- 
    2.在需要上报事件的地方比如注册登录支付成功后的回调里主动调用该函数
    lionuReportParams()
-->
            ";
        } elseif($scene == 3){
            //适合服务端上报的场景
            return "
            //某些适合服务端的场景请使用落地页或业务后端实现的语言请求Lionu上报接口即可(需要带上链接的qhclickid参数-由后端语言自行获取)
            curl -X GET 'https://testapi.digitalevers.com/SemReport/report360?method=ocpc&convert=".$convertType."&adv=".$advType."&qhclickid=12345'
        ";
        } else {
            return -1;
        }
    }
    
    /**
     * 获取baidu token
     */
    public function getBaiduInfo(){
        if(trim($this->username) != 'admin'){
            exit(json_encode([
                'code' => 200,
                'msg' => 'ok',
                'data' => []
            ], JSON_UNESCAPED_UNICODE));
        }
        $db = \Config\Database::connect();
        $conf = $db->query("SELECT `conf_key`,`conf_value` FROM u_conf_sem WHERE `conf_key` IN(?,?)", ['baiduToken','baiduLandpage'])->getResultArray();
        
        $data = [
            'conf'=>$conf,
            'convertTypes'=>$this->convertTypeBaidu
        ];
        echo json_encode([
            'code' => 200,
            'msg' => 'ok',
            'data' => $data
        ], JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * 生成百度上报联调js代码
     */
    public function createBaiduReportCode(){
        $token = $this->request->getPost('token', 'trim|xss_clean|strip_tags', '');
        $landpage = $this->request->getPost('landpage', 'trim|xss_clean|strip_tags', '');
        $convertType = $this->request->getPost('convertType', 'trim|xss_clean|strip_tags', '');
        if (! $this->validate([
            'token' => ['required'],
            'landpage' => ['required','valid_url'],
            'convertType' => ['required']
        ])) {
            //校验失败
            exit(json_encode(['code' => 199,'msg' => 'fail'], JSON_UNESCAPED_UNICODE));
        }
        
        $js = $this->getBaiduReportJsCode($convertType);
        //key、secret等信息写入数据库
        if(trim($this->username) == 'admin'){
            $db = \Config\Database::connect();
            $updateResult = $db->query("UPDATE u_conf_sem SET `conf_value`= CASE `conf_key`
                                            WHEN 'baiduToken' THEN ?
                                            WHEN 'baiduLandpage' THEN ?
                                        END WHERE `conf_key` IN('baiduToken','baiduLandpage')", [$token, $landpage]);
            
        }
        exit(json_encode(['code' => 200,'msg' => 'ok', 'data'=>$js], JSON_UNESCAPED_UNICODE));
    }
    
    /**
     * 向前端返送百度上报 js代码
     * @param unknown $scene
     * @param unknown $convertType
     * @param unknown $advType
     */
    private function getBaiduReportJsCode($convertType){
        //获取接口域名
        $domain = model('ConfigModel')->getDomain();
        //适合某些点击控件进行上报的场景
        return "<!-- 1.将下列代码放在联调URL页面的底部，置于</body>标签之上 。如果其他监测事件已经添加过下列代码，则无需重复添加 -->
<script type='text/javascript'>
    (function() {
      document.addEventListener('DOMContentLoaded', function() {
        function reportParams(button) {
          const urlParams = new URLSearchParams(window.location.search);
          const params = Object.fromEntries(urlParams.entries());
          if (params.bd_vid) {
            //console.log(params.bd_vid);
            const convertValue = button.getAttribute('data-convert');
            const requestUrl = 'https://".$domain."/SemReport/reportBaidu?convert=' + convertValue + '&' + new URLSearchParams(params).toString();
            fetch(requestUrl, {
              method: 'GET',
              headers: {
                'Content-Type': 'application/json'
              }
            }).then(response => {
              if (response.ok) {
                return response.json();
              }
              throw new Error('Network response was not ok.');
            }).then(data => {
              //console.log(data);
            }).catch(error => {
              console.error('There has been a problem with your fetch operation:', error);
            });
          }
        }
        // 获取具有 lionu 类名的按钮
        const lionuButtons = document.querySelectorAll('.lionu');
        if (lionuButtons) {
            //console.log(lionuButtons)
            // 添加点击事件监听器
            lionuButtons.forEach(function(button) {
              button.addEventListener('click', function() {
                reportParams(this);
              });
            });
        }
      })
    })();
</script>
<!--
    2.在需要上报事件的控件上（通常为button按钮或者a标签）增加 css样式类: lionu，并添加 data-convert属性
    例：<button class='... lionu' data-convert='".$convertType."'  ...>按钮</button>
           或者 <a class='... lionu' data-convert='".$convertType."'  ...>a标签</a>
           或者 <div class='... lionu' data-convert='".$convertType."'  ...>其他按钮</div>
-->";
    }
    
    /**
     * 获取各渠道配置参数
     */
    public function getTotalConfig(){
        if(trim($this->username) != 'admin'){
            exit(json_encode([
                'code' => 200,
                'msg' => 'ok',
                'data' => []
            ], JSON_UNESCAPED_UNICODE));
        }
        $db = \Config\Database::connect();
        $conf = $db->query("SELECT `conf_key`,`conf_value` FROM `u_conf_sem` WHERE 1=1", [])->getResultArray();
        $conf = array_column($conf, null, 'conf_key');
        //dump($conf);
        $tabs = [
            'bing'=>[
                'form'=> [
                    'uetid'=> $conf['uetid']['conf_value']
                ]
            ],
            'baidu'=>[
                'form'=> [
                    'token'=> $conf['baiduToken']['conf_value'],
                    'debugUrl'=> $conf['baiduDebugUrl']['conf_value']
                 ]
            ],
            'tab360'=>[
                'form'=> [
                    'key'=> $conf['360key']['conf_value'],
                    'secret'=> $conf['360secret']['conf_value'],
                    'jzqs'=> $conf['360jzqs']['conf_value'],
                    'debugUrl'=> $conf['360DebugUrl']['conf_value']
                ]
            ],
            'tencent'=>[
                'form'=> [
                    'accessToken'=> isset($conf['txAccessToken']['conf_value']) ? $conf['txAccessToken']['conf_value'] : '',
                    'accountId'=> isset($conf['txAccountId']['conf_value']) ? $conf['txAccountId']['conf_value'] : '',
                    'actionSetId'=> isset($conf['txUserActionSetId']['conf_value']) ? $conf['txUserActionSetId']['conf_value'] : '',
                ]
            ],
            'uc'=>[
                'label'=>'UC汇川',
                'name'=> 'uc',
                'form'=> [
                    'token'=> isset($conf['ucToken']['conf_value']) ? $conf['ucToken']['conf_value'] : '',
                    'debugUrl'=> isset($conf['ucDebugUrl']['conf_value']) ? $conf['ucDebugUrl']['conf_value'] : ''
                ]
            ]
        ];
        $landpage = isset($conf['landpage']['conf_value']) ? $conf['landpage']['conf_value'] : '';
        $data = [
            'tabs'=>$tabs,
            'convertTypes'=>$this->convertTypeTotal,
            'ocpc'=>['landpage'=>$landpage]
        ];
        echo json_encode([
            'code' => 200,
            'msg' => 'ok',
            'data' => $data
        ], JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * 设置各渠道参数
     */
    public function setTotalConfig(){
        //bing
        $uetid = $this->request->getPost('uetid', 'trim|xss_clean|strip_tags', '');
        //baidu
        $token = $this->request->getPost('token', 'trim|xss_clean|strip_tags', '');
        //360
        $key = $this->request->getPost('key', 'trim|xss_clean|strip_tags', '');
        $secret = $this->request->getPost('secret', 'trim|xss_clean|strip_tags', '');
        $jzqs = $this->request->getPost('jzqs', 'trim|xss_clean|strip_tags', '');
        //tencent
        $accessToken = $this->request->getPost('accessToken', 'trim|xss_clean|strip_tags', '');
        $accountId = $this->request->getPost('accountId', 'trim|xss_clean|strip_tags', '');
        $actionSetId = $this->request->getPost('actionSetId', 'trim|xss_clean|strip_tags', '');
        $updateRaw = [
            'uetid'=>$uetid,
            'baiduToken'=>$token,
            '360key'=>$key,
            '360secret'=>$secret,
            '360jzqs'=>$jzqs,
            'txAccessToken'=>$accessToken,
            'txAccountId'=>$accountId,
            'txUserActionSetId'=>$actionSetId
        ];
        //去除数组空元素
        $updateArr = array_filter($updateRaw);
        $keys = array_keys($updateArr);
        $values = array_values($updateArr);
        if(count($keys) > 0 && count($values) > 0){
            $when = implode("' THEN ? WHEN '", $keys);
            $keys_str = implode("','", $keys);
            $updateSql = "UPDATE u_conf_sem SET `conf_value`= CASE `conf_key`
                                                WHEN '".$when."' THEN ?
                                            END WHERE `conf_key` IN('".$keys_str."')";
            $db = \Config\Database::connect();
            $updateResult = $db->query($updateSql, $values);
            $row = $updateResult->connID->affected_rows;
            if($row < 0){
                exit(json_encode(['code' => 199,'msg' => 'fail'], JSON_UNESCAPED_UNICODE));
            } else {
                exit(json_encode(['code' => 200,'msg' => 'ok'], JSON_UNESCAPED_UNICODE));
            }
        }
    }
    
    /**
     * 生成百度上报联调js代码
     */
    public function createReportCode(){
        $landpage = $this->request->getPost('landpage', 'trim|xss_clean|strip_tags', '');
        $convertType = $this->request->getPost('convertType', 'trim|xss_clean|strip_tags', '');
        if (! $this->validate([
            'landpage' => ['required','valid_url'],
            'convertType' => ['required']
        ])) {
            //校验失败
            exit(json_encode(['code' => 199,'msg' => 'fail'], JSON_UNESCAPED_UNICODE));
        }
        $jsHtmlCode = $this->getReportJsHtmlCode($convertType);
        
        //key、secret等信息写入数据库
        if(trim($this->username) == 'admin'){
            $db = \Config\Database::connect();
            $updateResult = $db->query("UPDATE u_conf_sem SET `conf_value`= ? WHERE `conf_key`='landpage'", [$landpage]);
            
        }
        exit(json_encode(['code' => 200,'msg' => 'ok', 'data'=>$jsHtmlCode], JSON_UNESCAPED_UNICODE));
    }
    
    /**
     * 向前端返送百度上报 js代码
     * @param unknown $scene
     * @param unknown $convertType
     * @param unknown $advType
     */
    private function getReportJsHtmlCode($convertType){
        //获取接口域名
        $domain = model('ConfigModel')->getDomain();
        //适合某些点击控件进行上报的场景
        return ["js"=>
            "<script>
    (function loadScript() {
      const script = document.createElement('script');
      script.src = './js/report.js?domain=".$domain."&v=' + Math.floor(Math.random() * 10000);
      script.async = true;
      script.onload = function () {console.log('report.js 加载完成');};
      script.onerror = function (e) {console.error('加载 report.js 失败', e);};
      document.body.appendChild(script);
    })();
</script>",
            "html"=>"<button class='... lionu' data-convert='".$convertType."'  ...>按钮</button>
<a class='... lionu' data-convert='".$convertType."'  ...>a标签</a>
<div class='... lionu' data-convert='".$convertType."'  ...>其他按钮</div>"];
    }
    
    
}
