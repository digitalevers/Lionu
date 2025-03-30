<?php 
namespace App\Controllers;

use App\Models\ConfigModel;

class SemReport extends BaseController
{
    protected $advTypeMap = [
        'psConvert'=>'ocpc_ps_convert',
        'msConvert'=>'ocpc_ms_convert',
        'zsConvert'=>'ocpc_zs_convert'
    ];
    
    /**
     * 360 ocpc上报接口
     * TODO 接口参数校验签名
     */
    public function report360(){
        // 获取表单数据
        $qhclickid = addslashes(trim($_GET['qhclickid']));
        if(!empty($qhclickid)){
            //上报ocpc数据
            $method = addslashes(trim($_GET['method']));
            if($method == 'ocpc'){   
                $advType = addslashes(trim($_GET['adv']));
                $convertType = addslashes(trim($_GET['convert']));
                if(!in_array($advType, array_keys($this->advTypeMap))){
                    exit(json_encode(['ok'=>199,'msg'=>'fail']));
                }
                
                $config360 = model('ConfigModel')->get360Config();
                $config360 = array_column($config360,null,'conf_key');
                //$convertType 如果是 submit或者order 需要传入trans_id参数 这里使用订单时间错的md5值作为trans_id
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
                //dump($config360);
                //exit;
                
                $signstr =  $config360['360secret']['conf_value'].$postdata_json;
                $header = ['App-Key:'.$config360['360key']['conf_value'],'Content-Type: application/json','App-Sign:'.md5($signstr)];
                $reportResult = requestPost('https://convert.dop.360.cn/uploadWebConvert', $postdata_json, $header);
                file_put_contents(WRITEPATH.'/logs/semReport.log',json_encode($header).'-'.$postdata_json.'-'.$reportResult."\r\n", FILE_APPEND);
                $resultJson = json_decode($reportResult,true);
                if($resultJson['errno'] == 0){
                    echo json_encode(['ok'=>200,'msg'=>'ok']);
                } else {
                    echo json_encode(['ok'=>199,'msg'=>'fail']);
                }
            } else {
                //记录日志
                echo json_encode(['ok'=>-2,'msg'=>'method error']);
            }
        } else {
            //记录日志
            echo json_encode(['ok'=>-3,'msg'=>'qhclickid error']);
        }
    }
}
