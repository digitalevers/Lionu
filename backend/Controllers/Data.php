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
        $sql = "select group_concat(pay_amount,',',pay_days order by pay_days SEPARATOR '|') AS pay_amount,plan_id FROM test.statistics_pay WHERE
 active_date>=? group by plan_id";
        $query = $db->query($sql,['2021-01-10']);
        $res = $query->getResultArray();
        foreach ($res as &$r){
            $r += $this->handleLtvBase($r['pay_amount']);
        }
        dump($res);
    }
    
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
        $start_date = isset($params['start_date']) ? (is_date($params['start_date']) ? date('Y-m-d',strtotime($params['start_date'])) : exit('start date error')) : '';
        $end_date = isset($params['end_date']) ? (is_date($params['end_date']) ? date('Y-m-d',strtotime($params['end_date'])) : exit('end date error')) : '';
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
        $base_sql = 'SELECT SUM(click_count) AS base_click,SUM(launch_count) AS base_launch,SUM(active_count) AS base_active,SUM(reg_count) AS base_reg,SUM(pay_amount) AS pay_total_amount,SUM(pay_count) AS pay_total_count,stat_date FROM statistics_base WHERE {where_key_str} GROUP BY stat_date';
        //echo $base_sql;
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
        $ltv_pay_sql = "SELECT GROUP_CONCAT(pay_amount,',',pay_days ORDER BY pay_days SEPARATOR '|') AS pay_amount,active_date FROM test.statistics_pay WHERE {where_key_str} GROUP BY active_date";
        $ltv_pay_arr = $this->query_and_get($db, $filter_params, $ltv_pay_sql,'active_date');
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
        //dump($base_arr);
        //exit;
        //留存 测试数据
        $retention_conf = [1,2,3,4,5,6,7,14,30,45,60,75,90,120];
        foreach ($base_arr as $date=>&$v){
            foreach ($retention_conf as $_conf){
                $v["ret{$_conf}"] = '12%';
                //dump($_conf);
            }
            $v['base_reg_rate'] = '10%';
            $v['pay_total_devices'] = '12';
            $v['pay_active_amount'] = '120';
            $v['pay_active_count'] = '120';
            $v['pay_new_amount'] = '120';
            $v['pay_new_device'] = '120';
            $v['pay_new_rate'] = '8%';
            $v['pay_arpu'] = '2.32';
            $v['pay_arppu'] = '5.8';
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
    
    private function channel($params){
        $appid = (isset($params['app_id']) && (intval($params['app_id']) > 0)) ? intval($params['app_id']) : exit('app id error');
        $start_date = isset($params['start_date']) ? (is_date($params['start_date']) ? date('Y-m-d',strtotime($params['start_date'])) : exit('start date error')) : '';
        $end_date = isset($params['end_date']) ? (is_date($params['end_date']) ? date('Y-m-d',strtotime($params['end_date'])) : exit('end date error')) : '';
        $start_pay_date = isset($params['start_pay_date']) ? (is_date($params['start_pay_date']) ? date('Y-m-d',strtotime($params['start_pay_date'])) : exit('start pay date error')) : '';
        $end_pay_date = isset($params['end_pay_date']) ? (is_date($params['end_pay_date']) ? date('Y-m-d',strtotime($params['end_pay_date'])) : exit('end pay date error')) : '';
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
        $base_sql = 'SELECT SUM(click_count) AS base_click,SUM(launch_count) AS base_launch,SUM(active_count) AS base_active,SUM(reg_count) AS base_reg,SUM(pay_amount) AS pay_total_amount,SUM(pay_count) AS pay_total_count,channel_id FROM statistics_base WHERE {where_key_str} GROUP BY channel_id';
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
        $ltv_pay_sql = "SELECT GROUP_CONCAT(pay_amount,',',pay_days ORDER BY pay_days SEPARATOR '|') AS pay_amount,channel_id FROM (SELECT SUM(pay_amount) AS pay_amount,channel_id,pay_days FROM test.statistics_pay WHERE {where_key_str} GROUP BY channel_id,pay_days) AS temp GROUP BY channel_id";
        $ltv_pay_arr = $this->query_and_get($db, $filter_params, $ltv_pay_sql,'channel_id');
        foreach ($ltv_pay_arr as &$r){
            $r += $this->handleLtvBase($ltv_conf,$r['pay_amount']);
        }
        
        foreach ($base_arr as $plan_id=>&$v){
            foreach ($ltv_conf as $_conf){
                $v["ltv{$_conf}"] = isset($ltv_pay_arr[$plan_id]["ltv{$_conf}"]) ? intval($ltv_pay_arr[$plan_id]["ltv{$_conf}"]) : 0;
            }
            $v['ltvc'] = isset($ltv_pay_arr[$plan_id]['ltvc']) ? intval($ltv_pay_arr[$plan_id]['ltvc']) : 0;
        }
        
        //留存 测试数据
        $retention_conf = [1,2,3,4,5,6,7,14,30,45,60,75,90,120];
        foreach ($base_arr as $date=>&$v){
            foreach ($retention_conf as $_conf){
                $v["ret{$_conf}"] = '12%';
                //dump($_conf);
            }
            $v['base_reg_rate'] = '10%';
            $v['pay_total_devices'] = '12';
            $v['pay_active_amount'] = '120';
            $v['pay_active_count'] = '120';
            $v['pay_new_amount'] = '120';
            $v['pay_new_device'] = '120';
            $v['pay_new_rate'] = '8%';
            $v['pay_arpu'] = '2.32';
            $v['pay_arppu'] = '5.8';
            $v['channel_name'] = '测试渠道';
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
    
    private function plan($params){
        $appid = (isset($params['app_id']) && (intval($params['app_id']) > 0)) ? intval($params['app_id']) : exit('app id error');
        $start_date = isset($params['start_date']) ? (is_date($params['start_date']) ? date('Y-m-d',strtotime($params['start_date'])) : exit('start date error')) : '';
        $end_date = isset($params['end_date']) ? (is_date($params['end_date']) ? date('Y-m-d',strtotime($params['end_date'])) : exit('end date error')) : '';
        $start_pay_date = isset($params['start_pay_date']) ? (is_date($params['start_pay_date']) ? date('Y-m-d',strtotime($params['start_pay_date'])) : exit('start pay date error')) : '';
        $end_pay_date = isset($params['end_pay_date']) ? (is_date($params['end_pay_date']) ? date('Y-m-d',strtotime($params['end_pay_date'])) : exit('end pay date error')) : '';
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
        $base_sql = 'SELECT SUM(click_count) AS base_click,SUM(launch_count) AS base_launch,SUM(active_count) AS base_active,SUM(reg_count) AS base_reg,SUM(pay_amount) AS pay_total_amount,SUM(pay_count) AS pay_total_count,plan_id FROM statistics_base WHERE {where_key_str} GROUP BY plan_id';
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
        $ltv_pay_sql = "SELECT GROUP_CONCAT(pay_amount,',',pay_days ORDER BY pay_days SEPARATOR '|') AS pay_amount,plan_id FROM (SELECT SUM(pay_amount) AS pay_amount,plan_id,pay_days FROM test.statistics_pay WHERE {where_key_str} GROUP BY plan_id,pay_days) AS temp GROUP BY plan_id";
        $ltv_pay_arr = $this->query_and_get($db, $filter_params, $ltv_pay_sql,'plan_id');
        foreach ($ltv_pay_arr as &$r){
            $r += $this->handleLtvBase($ltv_conf,$r['pay_amount']);
        }
        //TODO
        foreach ($base_arr as $plan_id=>&$v){
            foreach ($ltv_conf as $_conf){
                $v["ltv{$_conf}"] = isset($ltv_pay_arr[$plan_id]["ltv{$_conf}"]) ? intval($ltv_pay_arr[$plan_id]["ltv{$_conf}"]) : 0;
            }
            $v['ltvc'] = isset($ltv_pay_arr[$plan_id]['ltvc']) ? intval($ltv_pay_arr[$plan_id]['ltvc']) : 0;
        }
        
        //留存 测试数据
        $retention_conf = [1,2,3,4,5,6,7,14,30,45,60,75,90,120];
        foreach ($base_arr as $date=>&$v){
            foreach ($retention_conf as $_conf){
                $v["ret{$_conf}"] = '12%';
                //dump($_conf);
            }
            $v['base_reg_rate'] = '10%';
            $v['pay_total_devices'] = '12';
            $v['pay_active_amount'] = '120';
            $v['pay_active_count'] = '120';
            $v['pay_new_amount'] = '120';
            $v['pay_new_device'] = '120';
            $v['pay_new_rate'] = '8%';
            $v['pay_arpu'] = '2.32';
            $v['pay_arppu'] = '5.8';
            $v['plan_name'] = 'test';
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
        $filter_params = array_filter($filter_params);
        $where_key = $where_value = [];
        foreach ($filter_params as $k=>$v){
            $where_key[] = $k.'?';
            $where_value[] = $v;
        }
        $where_key_str = implode(' AND ', $where_key);
        $sql = str_replace('{where_key_str}', $where_key_str, $sql);
        //echo $sql;
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