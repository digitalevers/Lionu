<?php
/**
 * 计划模块相关接口
 */
namespace App\Controllers;

class Plan extends BaseController
{

    /**
     * 计划列表
     */
    public function list()
    {
        $post = $this->request->getVar(null, FILTER_SANITIZE_MAGIC_QUOTES); // todo
        $app_id = isset($post['app_id']) && (intval($post['app_id']) > 0) ? intval($post['app_id']) : '';
        $channel_id = isset($post['channel_id']) && (intval($post['channel_id']) > 0) ? intval($post['channel_id']) : '';
        $plan_name = isset($post['plan_name']) && ! empty(trim($post['plan_name'])) ? trim($post['plan_name']) : '';
        //分页
        $page = isset($post['page']) && (intval($post['page']) > 0) ? intval($post['page']) : 1;
        $page_size = isset($post['pageSize']) && (intval($post['pageSize']) > 0) ? intval($post['pageSize']) : 10;
        $offset = ($page - 1) * $page_size;
        $limit = $offset.','.$page_size;
        
        $filter_params = [
            'app_id=' => $app_id,
            'channel_id=' => $channel_id,
            'plan_name~LIKE~' => $plan_name
        ];
        $filter_params = array_filter($filter_params);
        
        $db = \Config\Database::connect();
        $plans = $this->select($db, 'u_plan', $filter_params,'*','',$limit);
        //TODO 应用信息和渠道信息从redis中获取
        $apps = $this->select($db, 'u_app',[],'id,app_name,app_os','id');
        $channels = $this->select($db, 'u_channel',[],'id,channel_name','id');
        //dump($channels);
        if(count($plans) > 0){
            foreach ($plans as &$plan){
                $plan['app_os'] = $apps[$plan['app_id']]['app_os'];
                $plan['app_name'] = $apps[$plan['app_id']]['app_name'];
                $plan['channel_name'] = $channels[$plan['channel_id']]['channel_name'];
                $plan['click_monitor_link'] = $plan['click_monitor_link'].'&plan_id='.$plan['id'];
            }
        }
        //获取计划总条数
        $total = $this->select($db, 'u_plan', $filter_params,'COUNT(id) AS total');
        $total = $total[0]['total'];
        
        echo json_encode([
            'code' => 200,
            'msg' => 'ok',
            'data' => ['total'=>$total,'plans'=>$plans]
        ], JSON_UNESCAPED_UNICODE);
    }

    private function select($db, $tb_name = '', $where_condition = [], $fields = '*',$group_key = '',$limit = '')
    {
        $where_keys = $where_values = [];
        foreach ($where_condition as $k => $v) {
            if (strpos($k, '~LIKE~') === false) {
                $where_keys[] = $k . '?';
            } else {
                $where_keys[] = str_replace('~LIKE~', '', $k) . ' LIKE "%?%" ';
            }
            $where_values[] = $v;
        }
        $where_keys_str = empty(implode(' AND ', $where_keys)) ? '1=1' : implode(' AND ', $where_keys);
        if(!empty($limit)){
            $select_sql = "SELECT {$fields} FROM {$tb_name} WHERE {$where_keys_str} LIMIT {$limit}";
        } else {
            $select_sql = "SELECT {$fields} FROM {$tb_name} WHERE {$where_keys_str}";
        }
        // echo $select_sql;
        // dump($where_values);
        $query = $db->query($select_sql, $where_values);
        $res = $query->getResultArray();
        if((count($res) > 0) && !empty($group_key)){
            $_res = [];
            foreach ($res as $v){
                if(isset($v[$group_key]) && !empty($v[$group_key])){
                    $_res[$v[$group_key]] = $v;
                }
            }
            return $_res;
        }
        return $res;
    }

    /**
     * 添加计划
     */
    public function add()
    {
        try {
            $now = date('Y-m-d H:i:s', time());
            $post = $this->request->getVar(null, FILTER_SANITIZE_MAGIC_QUOTES); // todo
            $plan_name = isset($post['plan_name']) && ! empty(trim($post['plan_name'])) ? trim($post['plan_name']) : exit('plan name empty');
            $app_id = isset($post['app_id']) && (intval($post['app_id']) > 0) ? intval($post['app_id']) : exit('app id empty');
            $channel_id = isset($post['channel_id']) && (intval($post['channel_id']) > 0) ? intval($post['channel_id']) : exit('channel id empty');
            $plan_count = isset($post['plan_count']) && (intval($post['plan_count']) > 1) ? intval($post['plan_count']) : 1;
            $click_monitor_link = $this->get_click_monitor_link($channel_id, $app_id);
            
            if ($plan_count == 1) {
                $add_data = array(
                    [
                        'app_id' => $app_id,
                        'channel_id' => $channel_id,
                        'plan_name' => $plan_name,
                        'add_time' => $now,
                        'click_monitor_link' => $click_monitor_link
                    ]
                );
            } else {
                $add_data = [];
                for ($i = 1; $i <= $plan_count; $i ++) {
                    $add_data[] = [
                        'app_id' => $app_id,
                        'channel_id' => $channel_id,
                        'plan_name' => $plan_name . '-' . $i,
                        'add_time' => $now,
                        'click_monitor_link' => $click_monitor_link
                    ];
                }
            }
            
            $db = \Config\Database::connect();
            //$db->setDatabase('test');
            $res = $this->insert($db, 'u_plan', $add_data);
            if ($res->resultID == true) {
                echo json_encode([
                    'code' => 200,
                    'msg' => 'ok'
                ]);
            } else {
                echo json_encode([
                    'code' => 199,
                    'msg' => $db->error()['message']
                ]);
            }
        } catch (\Exception $e) {
            echo json_encode([
                'code' => 198,
                'msg' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * 添加计划UI初始化
     * TODO 使用数据模型获取应用和渠道数据
     */
    public function addInit(){
        $db = \Config\Database::connect();
        //TODO 应用信息和渠道信息从redis中获取
        $_apps = [];
        $apps = $this->select($db, 'u_app',[],'id AS app_id,app_name,app_os');
        if(count($apps) > 0){
            foreach ($apps as $app){
                if(!isset($_apps[$app['app_os']]['data'])){
                    $_apps[$app['app_os']]['app_platform'] = $this->_get_platform($app['app_os']);
                    $_apps[$app['app_os']]['app_os'] = $app['app_os'];
                }
                $_apps[$app['app_os']]['data'][] = $app;
            }
        }
        //dump($_apps);
        $channels = $this->select($db, 'u_channel',[],'id,channel_name');
        $data = [
            'apps'=>array_values($_apps),
            'channels'=>$channels
        ];
        echo json_encode([
            'code' => 200,
            'msg' => 'ok',
            'data'=>$data
        ]);
    }
    
    private function _get_platform($app_os){
        switch ($app_os){
            case 1:
                return 'Android应用';
                break;
            case 2:
                return 'IOS应用';
                break;
            /* case 3:
                return 'H5应用';
                break;
            case 4:
                return '小程序';
                break;
            case 4:
                return 'Unity';
                break; */
            default:
                return 'Android应用';
                break;
        }
    }

    /**
     * 获取相应渠道的点击监测链接
     * 
     * @param unknown $channel_id            
     * @param unknown $app_id            
     * @throws \Exception
     * @return string
     */
    private function get_click_monitor_link($channel_id, $app_id)
    {
        $db = \Config\Database::connect();
        //$db->setDatabase('test');
        $select_sql = "SELECT conf_value FROM u_conf WHERE conf_key=?";
        $domain = $db->query($select_sql, ['SDKDOMAIN'])->getRowArray();
        if (! isset($domain['conf_value']) || empty($domain['conf_value'])) {
            throw new \Exception('sdkdomain error');
            exit();
        }
        $host = $domain['conf_value'];
        $select_sql = "SELECT app_os FROM u_app WHERE id=?";
        $app = $db->query($select_sql, [$app_id]);
        $app_os = $app->getRowArray();
        if (! isset($app_os['app_os']) || empty($app_os['app_os'])) {
            throw new \Exception('app os error');
            exit();
        }
        $app_os = $app_os['app_os'];
        switch ($app_os) {
            case 1:
                $app_os = 'android';
                break;
            case 2:
                $app_os = 'ios';
                break;
            default:
                $app_os = 'android';
                break;
        }
        // MySQL 低于5.7 不能直接在SQL语句中使用json语法 故需要解析json
        // $select_sql = "SELECT click_monitor_link_tpl ->> '$.{$app_os}' AS link FROM u_channel WHERE id=?";
        $select_sql = "SELECT click_monitor_link_tpl AS link FROM u_channel WHERE id=?";
        $channel = $db->query($select_sql, [
            $channel_id
        ]);
        $link = $channel->getRowArray()['link'];
        if (empty($link)) {
            throw new \Exception('未添加相关渠道');
            exit();
        }
        $link = json_decode($link, true);
        $link = $link[$app_os];
        
        if (! isset($link) || empty($link)) {
            throw new \Exception('monitor link error');
            exit();
        }
        return 'http://'.$host . "/receive/{$app_os}Click?channel_id=" . $channel_id . "&app_id=" . $app_id . "&" . $link;
    }

    /**
     * 底层写入数据函数
     * 
     * @param object $db
     *            数据库连接对象
     * @param string $tb_name
     *            表名
     * @param array $new_data
     *            待写入数据（ 二维数组）
     * @return object 执行结果对象
     */
    private function insert($db, $tb_name = '', $new_data = [])
    {
        if (! isset($new_data[0])) {
            throw new \Exception('数据对象错误');
        }
        $fields = implode(',', array_keys($new_data[0]));
        $flags = $values = [];
        foreach ($new_data as $v) {
            $flags[] = implode(',', array_fill(0, count($new_data[0]), '?'));
            $values = array_merge($values, array_values($v));
        }
        $flags = implode('),(', $flags);
        $insert_sql = "INSERT INTO {$tb_name}(" . $fields . ") VALUES (" . $flags . ")";
        $res = $db->query($insert_sql, $values);
        return $res;
    }

    /**
     * 删除计划
     */
    public function del()
    {
        $post = $this->request->getVar(null, FILTER_SANITIZE_MAGIC_QUOTES); // todo
        $plan_id = isset($post['plan_id']) && (intval($post['plan_id']) > 0) ? intval($post['plan_id']) : exit('plan id empty');
        $db = \Config\Database::connect();
        //$db->setDatabase('test');
        $filter_params = [
            'id=' => $plan_id
        ];
        $res = $this->delete($db, 'u_plan', $filter_params);
        if ($res->resultID == true) {
            echo json_encode([
                'code' => 200,
                'msg' => 'ok',
                'rows' => $res->connID->affected_rows
            ]);
        } else {
            echo json_encode([
                'code' => 199,
                'msg' => $db->error()['message']
            ]);
        }
    }

    private function delete($db, $tb_name = '', $condition = [])
    {
        $condition = array_filter($condition);
        $where_key = $where_value = [];
        foreach ($condition as $k => $v) {
            $where_key[] = $k . '?';
            $where_value[] = $v;
        }
        $where_key_str = implode(' AND ', $where_key);
        $delete_sql = "DELETE FROM {$tb_name} WHERE {$where_key_str}";
        //echo $delete_sql;
        //dump($where_value);
        $res = $db->query($delete_sql, $where_value);
        return $res;
    }
    
}