<?php
/**
 * 系统设置模块
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
    
    //360 移送搜索转化类型定义
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
        $res = $db->query("SELECT `conf_key`,`conf_value` FROM u_conf WHERE `conf_key` IN(?,?,?,?)", ['360key','360secret','360landpage','360jzqs'])->getResultArray();
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
                $updateResult = $db->query("UPDATE u_conf SET `conf_value`= CASE `conf_key`
                                            WHEN '360key' THEN ?
                                            WHEN '360secret' THEN ?
                                            WHEN '360landpage' THEN ?
                                            WHEN '360jzqs' THEN ?
                                        END WHERE `conf_key` IN('360key','360secret','360landpage','360jzqs')", [$key, $secret, $landpage, $jzqs]);
            } else {
                $updateResult = $db->query("UPDATE u_conf SET `conf_value`= CASE `conf_key`
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
     * 向前端返送js代码
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
            //某些适合服务端的场景请使用落地页或业务后端实现的语言请求Lionu上报接口即可(需要带上链接的qhclickid参数)
            curl -X GET 'https://testapi.digitalevers.com/SemReport/report360?method=ocpc&convert=".$convertType."&adv=".$advType."&qhclickid=12345'
        ";
        } else {
            return -1;
        }
    }
}
