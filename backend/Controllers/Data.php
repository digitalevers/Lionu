<?php
/**
 * 统计数据模块相关接口
 */
namespace App\Controllers;

class Data extends BaseController{
    
    public function entrance(){
        $post = $this->request->getVar(null,FILTER_SANITIZE_MAGIC_QUOTES);

        switch ($post['dimension']){
            case 1:
                $this->date($post);
                break;
            case 2:
                $this->channel($post);
                break;
            case 3:
                $this->plan($post);
                break;
            case 4:
                $this->user($post);
                break;
            default:
                exit('dimension params error');
                break;
        }
    }
    
    //ltv base data
    public function ltvBase(){
        $db = \Config\Database::connect();
        //$db->setDatabase('test');
        $sql = "select group_concat(pay_amount,',',pay_days order by pay_days SEPARATOR '|') AS pay_amount,plan_id FROM statistics_pay WHERE active_date>=? group by plan_id";
        $query = $db->query($sql,['2021-01-10']);
        $res = $query->getResultArray();
        foreach ($res as &$r){
            $r += $this->handleLtvBase($r['pay_amount']);
        }
        dump($res);
    }
    
    /**
     * 统计LTV基础方法
     * @param unknown $ltv_conf
     * @param unknown $r_data
     * @return number[]|mixed[]|unknown[]
     */
    private function handleLtvBase($ltv_conf,$r_data){
        $conf = [1,2,3,4,5,6,7,8,9,10];
        $temp = explode('|', $r_data);
        /* $data = [
         ['pay_amount'=>10,'pay_days'=>1],
         ['pay_amount'=>20,'pay_days'=>2],
         ['pay_amount'=>5,'pay_days'=>3],
         ['pay_amount'=>2,'pay_days'=>4],
         ['pay_amount'=>80,'pay_days'=>10]
         ]; */
        $data = [];
        foreach ($temp as $t){
            $temp2 = explode(',', $t);
            $data[] = ['pay_amount'=>$temp2[0],'pay_days'=>$temp2[1]];
        }
        
        $ltv = [];
        $temp_pay = 0;
        foreach ($data as $v){
            /* if($v['pay_days'] > max($conf)){
             break;
             } */
            $temp_pay += $v['pay_amount'];
            $ltv[$v['pay_days']] =  $temp_pay;
        }
        
        $res = [];
        
        foreach ($conf as $c){
            foreach ($ltv as $k=>$l){
                if($k <= $c){
                    $res['ltv'.$c] = $l;
                } else {
                    break;
                }
            }
        }
        $res['ltvc'] = count($ltv) > 0 ? end($ltv) : 0;
        return $res;
    }
    
    /**
     * date
     */
    private function date($params){
        $appid = (isset($params['app_id']) && (intval($params['app_id']) > 0)) ? intval($params['app_id']) : exit('app id error');
        $start_date = isset($params['statis_start_date']) ? ($this->is_date($params['statis_start_date']) ? date('Y-m-d',strtotime($params['statis_start_date'])) : exit('statis start date error')) : '';
        $end_date = isset($params['statis_end_date']) ? ($this->is_date($params['statis_end_date']) ? date('Y-m-d',strtotime($params['statis_end_date'])) : exit('statis end date error')) : '';
        $channel_id = (isset($params['channel_id']) && (intval($params['channel_id']) > 0)) ? intval($params['channel_id']) : '';
        $plan_id = (isset($params['plan_id']) && (intval($params['plan_id']) > 0)) ? intval($params['plan_id']) : '';
        $uid = (isset($params['uid']) && (intval($params['uid']) > 0)) ? intval($params['uid']) : '';
        
        $db = \Config\Database::connect();
        //$db->setDatabase('test');
        $filter_params = [
            'app_id='=>$appid,
            'stat_date>='=>$start_date,
            'stat_date<='=>$end_date,
            'channel_id='=>$channel_id,
            'plan_id='=>$plan_id,
            'uid='=>$uid
        ];
        $base_sql = 'SELECT SUM(click_count) AS base_click,SUM(launch_count) AS base_launch,SUM(active_count) AS base_active,SUM(reg_count) AS base_reg, SUM(onlyreg_count) AS base_reg_device, SUM(pay_amount) AS pay_total_amount,SUM(pay_count) AS pay_total_count,stat_date FROM statistics_base WHERE {where_key_str} GROUP BY stat_date';
        $base_arr = $this->query_and_get($db, $filter_params, $base_sql, 'stat_date');
        //dump($base_arr);
        //exit;
       
        //ltv & ltvc-到当前日期的ltv
        $ltv_conf = [1,2,3,4,5,6,7,14,30,45,60,75,90,120];
        $filter_params = [
            'app_id='=>$appid,
            'active_date>='=>$start_date,
            'active_date<='=>$end_date,
            'channel_id='=>$channel_id,
            'plan_id='=>$plan_id,
            'uid='=>$uid
        ];
        $ltv_pay_sql = "SELECT GROUP_CONCAT(pay_amount, ',', pay_days ORDER BY pay_days SEPARATOR '|') AS pay_amount,active_date FROM statistics_pay WHERE {where_key_str} GROUP BY active_date";
        //echo $ltv_pay_sql;
        $ltv_pay_arr = $this->query_and_get($db, $filter_params, $ltv_pay_sql,'active_date');
        //dump($ltv_pay_arr);
        foreach ($ltv_pay_arr as &$r){
            $r += $this->handleLtvBase($ltv_conf,$r['pay_amount']);
        }
        foreach ($base_arr as $date=>&$v){
            foreach ($ltv_conf as $_conf){
                $v["ltv{$_conf}"] = isset($ltv_pay_arr[$date]["ltv{$_conf}"]) ? intval($ltv_pay_arr[$date]["ltv{$_conf}"]) : 0;
                //dump($_conf);
            }
            $v['ltvc'] = isset($ltv_pay_arr[$date]['ltvc']) ? intval($ltv_pay_arr[$date]['ltvc']) : 0;
        }
        //dump($ltv_pay_arr);
    
        //留存
        $retention_conf = [2,3,4,5,6,7,14,30,45,60,75,90,120];
        $filter_params = [
            'app_id='=>$appid,
            'active_date>='=>$start_date,
            'active_date<='=>$end_date,
            'channel_id='=>$channel_id,
            'plan_id='=>$plan_id,
            'uid='=>$uid
        ];
        $retention_sql = "SELECT retention_count,retention_days,active_date FROM statistics_retention WHERE {where_key_str}";
        $retention_arr = $this->query_and_get($db, $filter_params, $retention_sql,'');
        //以active_date作为key组装retention数据
        $_retention_arr = [];
        foreach ($retention_arr as $rv){
            if(!isset($_retention_arr[$rv['active_date']][$rv['retention_days']])){
                $_retention_arr[$rv['active_date']][$rv['retention_days']] = $rv;
            }
        }
        //dump($_retention_arr);
        //查询 statistics_pay 表付费统计
        $filter_params = [
            'app_id='=>$appid,
            'pay_date>='=>$start_date,
            'pay_date<='=>$end_date,
            'channel_id='=>$channel_id,
            'plan_id='=>$plan_id,
            'uid='=>$uid
        ];
        $pay_detail_sql = 'SELECT SUM(pay_total_devices) AS pay_total_devices, SUM(pay_active_amount) AS pay_active_amount,SUM(pay_active_count) AS pay_active_count, SUM(pay_new_amount) AS pay_new_amount, SUM(pay_new_devices) AS pay_new_device, pay_date FROM statistics_pay WHERE {where_key_str} GROUP BY pay_date';
        $pay_detail_arr = $this->query_and_get($db, $filter_params, $pay_detail_sql, 'pay_date');
        //dump($base_arr);
        //exit;
        foreach ($base_arr as $date=>&$v){
            foreach ($retention_conf as $_conf){
                $retention_count = isset($_retention_arr[$date][$_conf]['retention_count']) ? $_retention_arr[$date][$_conf]['retention_count'] : 0;
                $v["ret{$_conf}"] = $v['base_active'] == 0 ? '-' : (($retention_count / $v['base_active']) * 100).'%';
                //dump($_conf);
            }
            $v['base_reg_rate'] = $v['base_active'] == 0 ?  '-' : (($v['base_reg_device'] / $v['base_active'])*100).'%';    //TODO 考虑当日注册率(即当天激活当天注册)
            $v['pay_total_devices'] = isset($pay_detail_arr[$date]['pay_total_devices']) ? $pay_detail_arr[$date]['pay_total_devices'] : 0;         //总付费设备数（新+旧）
            $v['pay_active_amount'] = isset($pay_detail_arr[$date]['pay_active_amount']) ? $pay_detail_arr[$date]['pay_active_amount'] : 0;        //激活付费金额
            $v['pay_active_count'] = isset($pay_detail_arr[$date]['pay_active_count']) ? $pay_detail_arr[$date]['pay_active_count'] : 0;         //激活付费次数
            $v['pay_new_amount'] = isset($pay_detail_arr[$date]['pay_new_amount']) ? $pay_detail_arr[$date]['pay_new_amount'] : 0;           //新增付费金额
            $v['pay_new_device'] = isset($pay_detail_arr[$date]['pay_new_device']) ? $pay_detail_arr[$date]['pay_new_device'] : 0;           //新增付费设备数
            $v['pay_new_rate'] = $v['base_active'] == 0 ? '-' : (($v['pay_new_device'] / $v['base_active']) * 100).'%';              //新增付费率(新增付费设备 / 总激活设备数)
            $v['pay_arpu'] = $v['base_active'] == 0 ? '-' : $v['pay_total_amount'] / $v['base_active'];                                      //arpu 总付费金额 / 总激活设备数
            $v['pay_arppu'] = $v['pay_total_devices'] == 0 ? '-' : $v['pay_total_amount'] / $v['pay_total_devices'];                         //arppu 总付费金额 / 总付费设备数
        }
        
        $result = array_values($base_arr);
        //分页
        $page_size = (isset($params['pageSize']) && (intval($params['pageSize']) > 0)) ? intval($params['pageSize']) : 10;
        $page = (isset($params['page']) && (intval($params['page']) > 0)) ? intval($params['page']) : 1;
        
        $start = ($page - 1) * $page_size ;
        $_slice_ = array_slice($result, $start,$page_size);
        $total = count($result);
        //根据前端参数过滤
        $options = explode(',',$params['custom_params'].',stat_date');
        //dump($options);
        //exit;
        foreach ($_slice_ as &$v){
            foreach ($v as $_k=>$_v){
                if(!in_array($_k, $options)){
                    unset($v[$_k]);
                }
            }
        }
        
        echo json_encode(['code'=>200,'msg'=>'ok','data'=>['total'=>$total,'rows'=>$_slice_]],JSON_UNESCAPED_UNICODE);
    }
    
    private function plan($params){
        $appid = (isset($params['app_id']) && (intval($params['app_id']) > 0)) ? intval($params['app_id']) : exit('app id error');
        $start_date = isset($params['statis_start_date']) ? ($this->is_date($params['statis_start_date']) ? date('Y-m-d',strtotime($params['statis_start_date'])) : exit('statis_start_date error')) : '';
        $end_date = isset($params['statis_end_date']) ? ($this->is_date($params['statis_end_date']) ? date('Y-m-d',strtotime($params['statis_end_date'])) : exit('statis_end_date error')) : '';
        $start_pay_date = isset($params['pay_start_date']) ? ($this->is_date($params['pay_start_date']) ? date('Y-m-d',strtotime($params['pay_start_date'])) : exit('pay_start_date error')) : '';
        $end_pay_date = isset($params['pay_end_date']) ? ($this->is_date($params['pay_end_date']) ? date('Y-m-d',strtotime($params['pay_end_date'])) : exit('pay_end_date error')) : '';
        //
        $channel_id = (isset($params['channel_id']) && (intval($params['channel_id']) > 0)) ? intval($params['channel_id']) : '';
        $plan_id = (isset($params['plan_id']) && (intval($params['plan_id']) > 0)) ? intval($params['plan_id']) : '';
        $uid = (isset($params['uid']) && (intval($params['uid']) > 0)) ? intval($params['uid']) : '';
        $db = \Config\Database::connect();
        //$db->setDatabase('test');
        $filter_params = [
            'app_id='=>$appid,
            'stat_date>='=>$start_date,
            'stat_date<='=>$end_date,
            'channel_id='=>$channel_id,
            'plan_id='=>$plan_id,
            'uid='=>$uid
        ];
        $base_sql = 'SELECT SUM(click_count) AS base_click,SUM(launch_count) AS base_launch,SUM(active_count) AS base_active,SUM(reg_count) AS base_reg,SUM(onlyreg_count) AS base_reg_device,SUM(pay_amount) AS pay_total_amount,SUM(pay_count) AS pay_total_count,plan_id FROM statistics_base WHERE {where_key_str} GROUP BY plan_id';
        $base_arr = $this->query_and_get($db, $filter_params, $base_sql, 'plan_id');
        
        //ltv & ltvc
        $ltv_conf = [1,2,3,4,5,6,7,14,30,45,60,75,90,120];
        $filter_params = [
            'app_id='=>$appid,
            'active_date>='=>$start_date,
            'active_date<='=>$end_date,
            'channel_id='=>$channel_id,
            'plan_id='=>$plan_id,
            'uid='=>$uid
        ];
        $ltv_pay_sql = "SELECT GROUP_CONCAT(pay_amount,',',pay_days ORDER BY pay_days SEPARATOR '|') AS pay_amount,plan_id FROM (SELECT SUM(pay_amount) AS pay_amount,plan_id,pay_days FROM statistics_pay WHERE {where_key_str} GROUP BY plan_id,pay_days) AS temp GROUP BY plan_id";
        //echo $ltv_pay_sql;
        $ltv_pay_arr = $this->query_and_get($db, $filter_params, $ltv_pay_sql,'plan_id');
        foreach ($ltv_pay_arr as &$r){
            $r += $this->handleLtvBase($ltv_conf,$r['pay_amount']);
        }
        foreach ($base_arr as $_pid=>&$v){
            foreach ($ltv_conf as $_conf){
                $v["ltv{$_conf}"] = isset($ltv_pay_arr[$_pid]["ltv{$_conf}"]) ? intval($ltv_pay_arr[$_pid]["ltv{$_conf}"]) : 0;
            }
            $v['ltvc'] = isset($ltv_pay_arr[$_pid]['ltvc']) ? intval($ltv_pay_arr[$_pid]['ltvc']) : 0;
        }
        //dump($ltv_pay_arr);
        //exit;
        
        //计划信息map
        $plan_info_sql = "SELECT id,plan_name FROM u_plan WHERE 1=1";
        $plan_info_map = $this->query_and_get($db, array(), $plan_info_sql,'id');
        //dump($base_arr);
        //exit;
        //留存
        $retention_conf = [2,3,4,5,6,7,14,30,45,60,75,90,120];
        $filter_params = [
            'app_id='=>$appid,
            'active_date>='=>$start_date,
            'active_date<='=>$end_date,
            'channel_id='=>$channel_id,
            'plan_id='=>$plan_id,
            'uid='=>$uid
        ];
        $retention_sql = "SELECT SUM(retention_count) AS retention_count,plan_id,retention_days FROM statistics_retention WHERE {where_key_str} GROUP BY plan_id,retention_days";
        $retention_arr = $this->query_and_get($db, $filter_params, $retention_sql,'');
        //dump($retention_arr);
        //exit;
        //以plan_id作为key组装retention数据
        $_retention_arr = [];
        foreach ($retention_arr as $rv){
            if(!isset($_retention_arr[$rv['plan_id']][$rv['retention_days']])){
                $_retention_arr[$rv['plan_id']][$rv['retention_days']] = $rv;
            }
        }
        
        //查询 statistics_pay 表付费统计
        $filter_params = [
            'app_id='=>$appid,
            'pay_date>='=>$start_date,
            'pay_date<='=>$end_date,
            'channel_id='=>$channel_id,
            'plan_id='=>$plan_id,
            'uid='=>$uid
        ];
        $pay_detail_sql = 'SELECT SUM(pay_total_devices) AS pay_total_devices, SUM(pay_active_amount) AS pay_active_amount,SUM(pay_active_count) AS pay_active_count, SUM(pay_new_amount) AS pay_new_amount, SUM(pay_new_devices) AS pay_new_device, pay_date FROM statistics_pay WHERE {where_key_str} GROUP BY plan_id';
        $pay_detail_arr = $this->query_and_get($db, $filter_params, $pay_detail_sql, 'plan_id');
        
        foreach ($base_arr as $_plan_id=>&$v){
            foreach ($retention_conf as $_conf){
                $retention_count = isset($_retention_arr[$_plan_id][$_conf]['retention_count']) ? $_retention_arr[$_plan_id][$_conf]['retention_count'] : 0;
                $v["ret{$_conf}"] = $v['base_active'] == 0 ? '-' : (($retention_count / $v['base_active']) * 100).'%';
            }
            $v['base_reg_rate'] = $v['base_active'] == 0 ?  '-' : (($v['base_reg_device'] / $v['base_active'])*100).'%';    //TODO 考虑当日注册率(即当天激活当天注册)
            $v['pay_total_devices'] = isset($pay_detail_arr[$_plan_id]['pay_total_devices']) ? $pay_detail_arr[$_plan_id]['pay_total_devices'] : 0;         //总付费设备数（新+旧）
            $v['pay_active_amount'] = isset($pay_detail_arr[$_plan_id]['pay_active_amount']) ? $pay_detail_arr[$_plan_id]['pay_active_amount'] : 0;        //激活付费金额
            $v['pay_active_count'] = isset($pay_detail_arr[$_plan_id]['pay_active_count']) ? $pay_detail_arr[$_plan_id]['pay_active_count'] : 0;         //激活付费次数
            $v['pay_new_amount'] = isset($pay_detail_arr[$_plan_id]['pay_new_amount']) ? $pay_detail_arr[$_plan_id]['pay_new_amount'] : 0;           //新增付费金额
            $v['pay_new_device'] = isset($pay_detail_arr[$_plan_id]['pay_new_device']) ? $pay_detail_arr[$_plan_id]['pay_new_device'] : 0;           //新增付费设备数
            $v['pay_new_rate'] = $v['base_active'] == 0 ? '-' : (($v['pay_new_device'] / $v['base_active']) * 100).'%';              //新增付费率(新增付费设备 / 总激活设备数)
            $v['pay_arpu'] = $v['base_active'] == 0 ? '-' : $v['pay_total_amount'] / $v['base_active'];                                      //arpu 总付费金额 / 总激活设备数
            $v['pay_arppu'] = $v['pay_total_devices'] == 0 ? '-' : $v['pay_total_amount'] / $v['pay_total_devices'];   
            
            $v['plan_name'] = $plan_info_map[$_plan_id]['plan_name'];;
        }
        
        $result = array_values($base_arr);
        //分页
        $page_size = (isset($params['pageSize']) && (intval($params['pageSize']) > 0)) ? intval($params['pageSize']) : 10;
        $page = (isset($params['page']) && (intval($params['page']) > 0)) ? intval($params['page']) : 1;
        
        $start = ($page - 1) * $page_size ;
        $_slice_ = array_slice($result, $start,$page_size);
        $total = count($result);
        //根据前端参数过滤
        $options = explode(',',$params['custom_params'].',plan_name');
        //dump($options);
        //exit;
        foreach ($_slice_ as &$v){
            foreach ($v as $_k=>$_v){
                if(!in_array($_k, $options)){
                    unset($v[$_k]);
                }
            }
        }
        
        echo json_encode(['code'=>200,'msg'=>'ok','data'=>['total'=>$total,'rows'=>$_slice_]],JSON_UNESCAPED_UNICODE);
    }
    
    private function channel($params){
        $appid = (isset($params['app_id']) && (intval($params['app_id']) > 0)) ? intval($params['app_id']) : exit('app id error');
        $start_date = isset($params['statis_start_date']) ? ($this->is_date($params['statis_start_date']) ? date('Y-m-d',strtotime($params['statis_start_date'])) : exit('statis_start_date error')) : '';
        $end_date = isset($params['statis_end_date']) ? ($this->is_date($params['statis_end_date']) ? date('Y-m-d',strtotime($params['statis_end_date'])) : exit('statis_end_date error')) : '';
        $start_pay_date = isset($params['pay_start_date']) ? ($this->is_date($params['pay_start_date']) ? date('Y-m-d',strtotime($params['pay_start_date'])) : exit('pay_start_date error')) : '';
        $end_pay_date = isset($params['pay_end_date']) ? ($this->is_date($params['pay_end_date']) ? date('Y-m-d',strtotime($params['pay_end_date'])) : exit('pay_end_date error')) : '';
        //
        $channel_id = (isset($params['channel_id']) && (intval($params['channel_id']) > 0)) ? intval($params['channel_id']) : '';
        $plan_id = (isset($params['plan_id']) && (intval($params['plan_id']) > 0)) ? intval($params['plan_id']) : '';
        $uid = (isset($params['uid']) && (intval($params['uid']) > 0)) ? intval($params['uid']) : '';
        $db = \Config\Database::connect();
        //$db->setDatabase('test');
        $filter_params = [
            'app_id='=>$appid,
            'stat_date>='=>$start_date,
            'stat_date<='=>$end_date,
            'channel_id='=>$channel_id,
            'plan_id='=>$plan_id,
            'uid='=>$uid
        ];
        $base_sql = 'SELECT SUM(click_count) AS base_click,SUM(launch_count) AS base_launch,SUM(active_count) AS base_active,SUM(reg_count) AS base_reg,SUM(onlyreg_count) AS base_reg_device,SUM(pay_amount) AS pay_total_amount,SUM(pay_count) AS pay_total_count,channel_id FROM statistics_base WHERE {where_key_str} GROUP BY channel_id';
        $base_arr = $this->query_and_get($db, $filter_params, $base_sql, 'channel_id');
        
        //ltv & ltvc
        $ltv_conf = [1,2,3,4,5,6,7,14,30,45,60,75,90,120];
        $filter_params = [
            'app_id='=>$appid,
            'active_date>='=>$start_date,
            'active_date<='=>$end_date,
            'channel_id='=>$channel_id,
            'plan_id='=>$plan_id,
            'uid='=>$uid
        ];
        $ltv_pay_sql = "SELECT GROUP_CONCAT(pay_amount,',',pay_days ORDER BY pay_days SEPARATOR '|') AS pay_amount,channel_id FROM (SELECT SUM(pay_amount) AS pay_amount,channel_id,pay_days FROM statistics_pay WHERE {where_key_str} GROUP BY channel_id,pay_days) AS temp GROUP BY channel_id";
        $ltv_pay_arr = $this->query_and_get($db, $filter_params, $ltv_pay_sql,'channel_id');
        foreach ($ltv_pay_arr as &$r){
            $r += $this->handleLtvBase($ltv_conf,$r['pay_amount']);
        }
        
        foreach ($base_arr as $_cid=>&$v){
            foreach ($ltv_conf as $_conf){
                $v["ltv{$_conf}"] = isset($ltv_pay_arr[$_cid]["ltv{$_conf}"]) ? intval($ltv_pay_arr[$_cid]["ltv{$_conf}"]) : 0;
            }
            $v['ltvc'] = isset($ltv_pay_arr[$_cid]['ltvc']) ? intval($ltv_pay_arr[$_cid]['ltvc']) : 0;
        }
        //渠道信息map
        $channel_info_sql = "SELECT id,channel_name FROM u_channel WHERE 1=1";
        $channel_info_map = $this->query_and_get($db, array(), $channel_info_sql,'id');
        
        //留存
        //dump($base_arr);
        //exit();
        $retention_conf = [2,3,4,5,6,7,14,30,45,60,75,90,120];
        $filter_params = [
            'app_id='=>$appid,
            'active_date>='=>$start_date,
            'active_date<='=>$end_date,
            'channel_id='=>$channel_id,
            'plan_id='=>$plan_id,
            'uid='=>$uid
        ];
        $retention_sql = "SELECT SUM(retention_count) AS retention_count,channel_id,retention_days FROM statistics_retention WHERE {where_key_str} GROUP BY channel_id,retention_days";
        $retention_arr = $this->query_and_get($db, $filter_params, $retention_sql,'');
        //dump($retention_arr);
        //exit;
        //以channel_id作为key组装retention数据
        $_retention_arr = [];
        foreach ($retention_arr as $rv){
            if(!isset($_retention_arr[$rv['channel_id']][$rv['retention_days']])){
                $_retention_arr[$rv['channel_id']][$rv['retention_days']] = $rv;
            }
        }
        //dump($_cid);
        //exit;
        //查询 statistics_pay 表付费统计
        $filter_params = [
            'app_id='=>$appid,
            'pay_date>='=>$start_date,
            'pay_date<='=>$end_date,
            'channel_id='=>$channel_id,
            'plan_id='=>$plan_id,
            'uid='=>$uid
        ];
        $pay_detail_sql = 'SELECT SUM(pay_total_devices) AS pay_total_devices, SUM(pay_active_amount) AS pay_active_amount,SUM(pay_active_count) AS pay_active_count, SUM(pay_new_amount) AS pay_new_amount, SUM(pay_new_devices) AS pay_new_device, pay_date FROM statistics_pay WHERE {where_key_str} GROUP BY channel_id';
        $pay_detail_arr = $this->query_and_get($db, $filter_params, $pay_detail_sql, 'channel_id');
        
        foreach ($base_arr as $_cid=>&$v){
            foreach ($retention_conf as $_conf){
                $retention_count = isset($_retention_arr[$_cid][$_conf]['retention_count']) ? $_retention_arr[$_cid][$_conf]['retention_count'] : 0;
                $v["ret{$_conf}"] = $v['base_active'] == 0 ? '-' : (($retention_count / $v['base_active']) * 100).'%';
                //dump($_conf);
            }            
            $v['base_reg_rate'] = $v['base_active'] == 0 ?  '-' : (($v['base_reg_device'] / $v['base_active'])*100).'%';    //TODO 考虑当日注册率(即当天激活当天注册)
            $v['pay_total_devices'] = isset($pay_detail_arr[$_cid]['pay_total_devices']) ? $pay_detail_arr[$_cid]['pay_total_devices'] : 0;         //总付费设备数（新+旧）
            $v['pay_active_amount'] = isset($pay_detail_arr[$_cid]['pay_active_amount']) ? $pay_detail_arr[$_cid]['pay_active_amount'] : 0;        //激活付费金额
            $v['pay_active_count'] = isset($pay_detail_arr[$_cid]['pay_active_count']) ? $pay_detail_arr[$_cid]['pay_active_count'] : 0;         //激活付费次数
            $v['pay_new_amount'] = isset($pay_detail_arr[$_cid]['pay_new_amount']) ? $pay_detail_arr[$_cid]['pay_new_amount'] : 0;           //新增付费金额
            $v['pay_new_device'] = isset($pay_detail_arr[$_cid]['pay_new_device']) ? $pay_detail_arr[$_cid]['pay_new_device'] : 0;           //新增付费设备数
            $v['pay_new_rate'] = $v['base_active'] == 0 ? '-' : (($v['pay_new_device'] / $v['base_active']) * 100).'%';              //新增付费率(新增付费设备 / 总激活设备数)
            $v['pay_arpu'] = $v['base_active'] == 0 ? '-' : $v['pay_total_amount'] / $v['base_active'];                                      //arpu 总付费金额 / 总激活设备数
            $v['pay_arppu'] = $v['pay_total_devices'] == 0 ? '-' : $v['pay_total_amount'] / $v['pay_total_devices'];  
            
            $v['channel_name'] = $channel_info_map[$_cid]['channel_name'];
        }
        
        $result = array_values($base_arr);
        //分页
        $page_size = (isset($params['pageSize']) && (intval($params['pageSize']) > 0)) ? intval($params['pageSize']) : 10;
        $page = (isset($params['page']) && (intval($params['page']) > 0)) ? intval($params['page']) : 1;
        
        $start = ($page - 1) * $page_size ;
        $_slice_ = array_slice($result, $start,$page_size);
        $total = count($result);
        //根据前端参数过滤
        $options = explode(',',$params['custom_params'].',channel_name');
        //dump($options);
        //exit;
        foreach ($_slice_ as &$v){
            foreach ($v as $_k=>$_v){
                if(!in_array($_k, $options)){
                    unset($v[$_k]);
                }
            }
        }
        
        echo json_encode(['code'=>200,'msg'=>'ok','data'=>['total'=>$total,'rows'=>$_slice_]],JSON_UNESCAPED_UNICODE);
    }
    
    private function user($params){
        
    }
    
    private function is_date($date) {
        $patten = "/^\d{4}[\-](0?[1-9]|1[012])[\-](0?[1-9]|[12][0-9]|3[01])(\s+(0?[0-9]|1[0-9]|2[0-3])\:(0?[0-9]|[1-5][0-9])(\:(0?[0-9]|[1-5][0-9]))?)?$/";
        if (preg_match($patten, $date)) {
            return true;
        } else {
            return false;
        }
    }
    
    private function query_and_get($db,$filter_params,$sql,$key = ''){
        $filter_params = array_filter($filter_params);  //过滤uid等空值
        $where_key = $where_value = [];
        foreach ($filter_params as $k=>$v){
            $where_key[] = $k.'?';
            $where_value[] = $v;
        }
        $where_key_str = implode(' AND ', $where_key);
        $sql = str_replace('{where_key_str}', $where_key_str, $sql);
        //dump($sql);
        //exit();
        $query = $db->query($sql,$where_value);
        $arr = $query->getResultArray();
        //dump($arr);
        //exit;
        if(!is_array($arr)){
            throw new \Exception('query error');
            exit();
        } else {
            if(count($arr) > 0 && !empty($key)){
                $_arr = [];
                foreach ($arr as $v){
                    if(!isset($v[$key])){
                        throw new \Exception('key error');
                        exit();
                    }
                    $_arr[$v[$key]] = $v;
                }
                return $_arr;
            }
            return $arr;
        }
    }
}