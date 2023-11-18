<?php
/**
 * 应用模块相关接口
 */
namespace App\Controllers;

class App extends NeedloginController
{

    /**
     * 应用列表
     */
    public function list()
    {
        $post = $this->request->getVar(null, FILTER_SANITIZE_MAGIC_QUOTES); // todo
        $app_step = isset($post['app_step']) ? intval($post['app_step']) : '';
        $db = \Config\Database::connect();
        // dump($db);
        //$db->setDatabase('test');
        if ($app_step == 3) {
            $sql = "SELECT id,app_name,app_os FROM u_app WHERE app_status=1 AND app_step=3 ORDER BY add_time DESC";
            $query = $db->query($sql);
            $apps = $query->getResultArray();
            echo json_encode([
                'code' => 200,
                'msg' => 'ok',
                'data' => [
                    'apps' => $apps
                ]
            ], JSON_UNESCAPED_UNICODE);
        } else {
            $page = isset($post['page']) ? intval($post['page']) : 1;
            $pageSize = isset($post['pageSize']) ? intval($post['pageSize']) : 10;
            $offset = ($page - 1) * $pageSize;
            
            $sql = "SELECT id,app_name,package_name,app_os,app_step,app_event,add_time,update_time FROM u_app WHERE app_status=1 ORDER BY add_time DESC LIMIT " . $offset . ',' . $pageSize;
            try{
            $query = $db->query("SELECT * FROM u_app WHERE 1=1");
            } catch (\Exception $e){
                echo $e->getMessage();
                exit;
            }
            $apps = $query->getResultArray();
            
            if (is_array($apps) && count($apps) > 0) {
                foreach ($apps as &$app) {
                    $app['app_event'] = json_decode($app['app_event'], true);
                }
            }
            $total_sql = "SELECT COUNT(id) AS total FROM u_app WHERE app_status=1";
            $query = $db->query($total_sql);
            $total = $query->getRowArray();
            $total = $total['total'];
            
            echo json_encode([
                'code' => 200,
                'msg' => 'ok',
                'data' => [
                    'total' => $total,
                    'apps' => $apps
                ]
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    public function add()
    {
        $post = $this->request->getVar(null, FILTER_SANITIZE_MAGIC_QUOTES); // todo
        $app_step = isset($post['step']) ? intval($post['step']) : 1;
        switch ($app_step) {
            case 1:
                $this->_add_step_one($post);
                break;
            case 2:
                $this->_add_step_two($post);
                break;
            case 3:
                $this->_add_step_three($post);
                break;
            default:
                break;
        }
    }

    /**
     * 添加步骤 step one
     */
    private function _add_step_one($post)
    {
        $now = date('Y-m-d H:i:s', time());
        $app_name = isset($post['app_name']) && ! empty(trim($post['app_name'])) ? trim($post['app_name']) : exit('app name empty');
        $app_os = isset($post['app_os']) ? intval($post['app_os']) : 1;
        $package_name = isset($post['package_name']) && ! empty(trim($post['package_name'])) ? trim($post['package_name']) : exit('package name empty');
        // 回传事件模板
        $app_event = json_encode([
            'active' => 0,
            'reg' => 0,
            'pay' => 0
        ]);
        $add_data = [
            'app_name' => $app_name,
            'package_name' => $package_name,
            'app_os' => $app_os,
            'add_time' => $now,
            'app_step' => 1,
            'app_event' => $app_event,
            'update_time' => $now,
            'note' => '',
            'app_status' => 1
        ];
        $db = \Config\Database::connect();
        $res = $this->insert($db, 'u_app', $add_data);
        // dump($res);
        if ($res->resultID == true) {
            $data = [
                'app_id' => $res->connID->insert_id
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

    /**
     * 添加步骤 step two
     */
    private function _add_step_two($post)
    {
        $app_id = isset($post['app_id']) && (intval($post['app_id']) > 0) ? intval($post['app_id']) : exit('app id empty');
        $update_data = [
            'id=' => $app_id,
            'app_step=' => 2
        ];
        
        $db = \Config\Database::connect();
        //$db->setDatabase('test');
        $update_sql = "UPDATE u_app SET `app_step`=2 WHERE id={$app_id}";
        $res = $db->query($update_sql);
        
        $select_sql = "SELECT app_event FROM u_app WHERE id={$app_id}";
        $query = $db->query($select_sql);
        $apps = $query->getResultArray();
        $app_event = json_decode($apps[0]['app_event']);
        echo json_encode([
            'code' => 200,
            'msg' => 'ok',
            'data' => [
                'app_event' => $app_event
            ]
        ]);
    }

    /**
     * 添加步骤 step three
     */
    private function _add_step_three($post)
    {
        $app_id = isset($post['app_id']) && (intval($post['app_id']) > 0) ? intval($post['app_id']) : exit('app id empty');
        $update_data = [
            'id=' => $app_id,
            'app_step=' => 3
        ];
        
        $db = \Config\Database::connect();
        //$db->setDatabase('test');
        $update_sql = "UPDATE u_app SET `app_step`=3 WHERE id={$app_id}";
        $res = $db->query($update_sql);
        
        echo json_encode([
            'code' => 200,
            'msg' => 'ok'
        ]);
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

    public function del()
    {
        $post = $this->request->getVar(null, FILTER_SANITIZE_MAGIC_QUOTES); // todo
        $app_id = isset($post['app_id']) && (intval($post['app_id']) > 0) ? intval($post['app_id']) : exit('app id empty');
        $db = \Config\Database::connect();
        //$db->setDatabase('test');
        $filter_params = [
            'id=' => $app_id
        ];
        $res = $this->delete($db, 'u_app', $filter_params);
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
        $res = $db->query($delete_sql, $where_value);
        return $res;
    }

    // todo
    public function modify()
    {
        $now = date('Y-m-d H:i:s', time());
        $post = $this->request->getVar(null, FILTER_SANITIZE_MAGIC_QUOTES); // todo
        $app_id = isset($post['app_id']) && (intval($post['app_id']) > 0) ? intval($post['app_id']) : exit('app id empty');
        $new_app_name = isset($post['new_app_name']) && ! empty(trim($post['new_app_name'])) ? trim($post['new_app_name']) : exit('new app name empty');
        $new_app_status = isset($post['new_app_status']) ? intval($post['new_app_status']) : 1;
        $where_condition = [
            'id=' => $app_id
        ];
        
        $update_data = [
            'app_name=' => $new_app_name,
            'update_time=' => $now,
            'note=' => 'app_name modify',
            'app_status=' => $new_app_status
        ];
        
        $db = \Config\Database::connect();
        //$db->setDatabase('test');
        $res = $this->update($db, 'app', $update_data, $where_condition);
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

    /**
     * app回传事件轮询接口
     */
    public function event_poll()
    {
        $post = $this->request->getVar(null, FILTER_SANITIZE_MAGIC_QUOTES); // todo
        $app_id = isset($post['app_id']) && (intval($post['app_id']) > 0) ? intval($post['app_id']) : exit('app id empty');
        $event_name = isset($post['event_name']) && ! empty(trim($post['event_name'])) ? trim($post['event_name']) : exit('event name empty');
        
        $db = \Config\Database::connect();
        //$db->setDatabase('test');
        $select_sql = "SELECT app_event FROM u_app WHERE id={$app_id}";
        $query = $db->query($select_sql);
        $apps = $query->getResultArray();
        $app_event = json_decode($apps[0]['app_event'], true);
        
        if (in_array($event_name, array_keys($app_event))) {
            echo json_encode([
                'code' => 200,
                'msg' => 'ok',
                'data' => [
                    'event_name' => $event_name,
                    'event_status' => $app_event[$event_name]
                ]
            ]);
        } else {
            echo json_encode([
                'code' => 199,
                'msg' => 'fail'
            ]);
        }
    }
}
