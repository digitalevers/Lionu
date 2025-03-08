<?php
/**
 * 渠道相关接口
 */
namespace App\Controllers;

class Channel extends NeedloginController
{
    /**
     * 渠道列表
     */
    public function list()
    {
        $post = $this->request->getVar(null, FILTER_SANITIZE_MAGIC_QUOTES); // todo
        $db = \Config\Database::connect();
        $page = isset($post['page']) ? intval($post['page']) : 1;
        $pageSize = isset($post['pageSize']) ? intval($post['pageSize']) : 10;
        $offset = ($page - 1) * $pageSize;
        
        $sql = "SELECT * FROM u_channel WHERE status = 1 ORDER BY add_time DESC LIMIT " . $offset . ',' . $pageSize;
        try{
            $query = $db->query($sql);
        } catch (\Exception $e){
            echo $e->getMessage();
            exit;
        }
        $channels = $query->getResultArray();
        if (is_array($channels) && count($channels) > 0) {
            foreach ($channels as &$channel) {
                $_monitor_url = json_decode($channel['click_monitor_link_tpl'], true);
                if(is_array($_monitor_url)){
                    if(isset($_monitor_url['android'])){
                        $channel['android_monitor_url'] = $_monitor_url['android'];
                    }
                    if(isset($_monitor_url['ios'])){
                        $channel['ios_monitor_url'] = $_monitor_url['ios'];
                    }
                }
            }
        }
        $total_sql = "SELECT COUNT(id) AS total FROM u_channel WHERE status = 1";
        $query = $db->query($total_sql);
        $total = $query->getRowArray();
        $total = $total['total'];
        
        echo json_encode([
            'code' => 200,
            'msg' => 'ok',
            'data' => [
                'total' => $total,
                'channels' => $channels
            ]
        ], JSON_UNESCAPED_UNICODE);
        
    }

    /**
     * 添加渠道
     */
    public function add()
    {
        $post = $this->request->getVar(null, FILTER_SANITIZE_MAGIC_QUOTES);
        $now = date('Y-m-d H:i:s', time());
        $channel_name = isset($post['channel_name']) && ! empty(trim($post['channel_name'])) ? trim($post['channel_name']) : exit('channel name empty');
        
        //查询该渠道是否已存在
        $db = \Config\Database::connect();
        $sql = "SELECT * FROM u_channel WHERE status = 1 AND channel_name='".$channel_name."'";
        $query = $db->query($sql);
        $channels = $query->getResultArray();
        if(is_array($channels) && count($channels) > 0){
            echo json_encode([
                'code' => 198,
                'msg' => "该渠道已存在"
            ]);
        } else {
            $android_monitor_url = isset($post['android_monitor_url']) && ! empty(trim($post['android_monitor_url'])) ? trim($post['android_monitor_url']) : '';
            $ios_monitor_url = isset($post['ios_monitor_url']) && ! empty(trim($post['ios_monitor_url'])) ? trim($post['ios_monitor_url']) : '';
            $_monitor_url = array('android'=>$android_monitor_url,'ios'=>$ios_monitor_url);
            $add_data = [
                'channel_name' => $channel_name,
                'click_monitor_link_tpl' => json_encode($_monitor_url, JSON_UNESCAPED_UNICODE),
                'status' => 1,
                'add_time' => $now,
            ];
            
            $res = $this->insert($db, 'u_channel', $add_data);
            // dump($res);
            if ($res->resultID == true) {
                $data = [
                    'channel_id' => $res->connID->insert_id
                ];
                echo json_encode([
                    'code' => 200,
                    'msg' => 'ok',
                    'data' => $data
                ]);
            } else {
                echo json_encode([
                    'code' => 199,
                    'msg' => $db->error()['message']
                ]);
            }
        }
    }

    private function insert($db, $tb_name = '', $new_data = [])
    {
        $fields = implode(',', array_keys($new_data));
        $flags = implode(',', array_fill(0, count($new_data), '?'));
        $values = array_values($new_data);
        $insert_sql = "INSERT INTO {$tb_name}(" . $fields . ") VALUES (" . $flags . ")";
        $res = $db->query($insert_sql, $values);
        return $res;
    }

    /**
     * 删除渠道
     */
    public function del()
    {
        $post = $this->request->getVar(null, FILTER_SANITIZE_MAGIC_QUOTES); // todo
        $channel_id = isset($post['channel_id']) && (intval($post['channel_id']) > 0) ? intval($post['channel_id']) : exit('channel id empty');
        $db = \Config\Database::connect();
        //$db->setDatabase('test');
        $filter_params = [
            'id=' => $channel_id
        ];
        $update_data = [
            'status=' => 0
        ];
        $res = $this->update($db, 'u_channel', $update_data, $filter_params);
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

    /**
     * 更新渠道
     */
    public function modify()
    {
        $now = date('Y-m-d H:i:s', time());
        $post = $this->request->getVar(null, FILTER_SANITIZE_MAGIC_QUOTES);
        $channel_id = isset($post['channel_id']) && (intval($post['channel_id']) > 0) ? intval($post['channel_id']) : exit('channel id empty');
        $android_monitor_url = isset($post['android_monitor_url']) && ! empty(trim($post['android_monitor_url'])) ? trim($post['android_monitor_url']) : '';
        $ios_monitor_url = isset($post['ios_monitor_url']) && ! empty(trim($post['ios_monitor_url'])) ? trim($post['ios_monitor_url']) : '';
        
        $where_condition = [
            'id=' => $channel_id
        ];
        $_monitor_url = array('android'=>$android_monitor_url,'ios'=>$ios_monitor_url);
        $update_data = [
            'click_monitor_link_tpl=' => json_encode($_monitor_url, JSON_UNESCAPED_UNICODE),
        ];
        
        $db = \Config\Database::connect();
        //$db->setDatabase('test');
        $res = $this->update($db, 'u_channel', $update_data, $where_condition);
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

    private function update($db, $tb_name = '', $update_data = [], $where_condition = [])
    {
        $where_fields = $where_values = [];
        foreach ($where_condition as $k => $v) {
            $where_fields[] = $k . '?';
            $where_values[] = $v;
        }
        $where_fields_str = implode(',', $where_fields);
        $fields = $values = [];
        foreach ($update_data as $k => $v) {
            $fields[] = $k . '?';
            $values[] = $v;
        }
        $fields_str = implode(',', $fields);
        $values = array_merge($values, $where_values);
        $update_sql = "UPDATE {$tb_name} SET {$fields_str} WHERE {$where_fields_str}";
        $res = $db->query($update_sql, $values);
        return $res;
    }
}
