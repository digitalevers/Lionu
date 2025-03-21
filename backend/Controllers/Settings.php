<?php
/**
 * 系统设置模块
 */
namespace App\Controllers;

class Settings extends NeedloginController
{
    public function getDomain(){
        $db = \Config\Database::connect();
        $domain = $db->query("SELECT * FROM u_conf WHERE `conf_key`=?", ['SDKDOMAIN'])->getRow('conf_value');
        echo json_encode([
            'code' => 200,
            'msg' => 'ok',
            'data' => $domain
        ], JSON_UNESCAPED_UNICODE);
    }
   
    /**
     * 报500错误 查看是否安装php-mbstring扩展
     */
    public function modifyDomain(){
        $configDomain = $this->request->getPost('configDomain', FILTER_SANITIZE_MAGIC_QUOTES);
        if (! $this->validate([
            //'configDomain' => ['required', 'regex_match[/^(?!-)[a-zA-Z0-9-]{1,63}(?<!-)(\.[a-zA-Z0-9-]{1,63}(?<!-))*\.[a-zA-Z]{2,}$/]']
            'configDomain' => ['required', 'valid_url']
        ])) {
            //校验失败
            exit(json_encode([
                'code' => 199,
                'msg' => 'fail'
            ], JSON_UNESCAPED_UNICODE));
        }
        //admin才有更改权限
        if(trim($this->username) == 'admin'){
            //dump($configDomain);
            $db = \Config\Database::connect();
            $updateResult = $db->query("UPDATE u_conf SET `conf_value`=? WHERE `conf_key`=?", [$configDomain, 'SDKDOMAIN']);
            //dump($updateResult->resultID); //fasle sql执行错误 true sql执行成功
            $row = $updateResult->connID->affected_rows; //-1 sql执行错误 0未更新 >0 更新行数
            if($row < 0){
                exit(json_encode(['code' => 199,'msg' => 'fail'], JSON_UNESCAPED_UNICODE));
            } else {
                exit(json_encode(['code' => 200,'msg' => 'ok'], JSON_UNESCAPED_UNICODE));
            }
        } else {
            exit(json_encode(['code' => 199,'msg' => '无修改权限'], JSON_UNESCAPED_UNICODE));
        }
    }
    
    public function modifyPwd(){
        $post = $this->request->getPost(null, FILTER_SANITIZE_MAGIC_QUOTES);
        if (! $this->validate([
            'adminPassword' => ['required', 'max_length[20]', 'min_length[6]'],
            'confirmPassword' => ['required', 'max_length[20]', 'min_length[6]', 'matches[adminPassword]'],
        ])) {
            //校验失败
            exit(json_encode([
                'code' => 199,
                'msg' => 'fail'
            ], JSON_UNESCAPED_UNICODE));
        }
        $pwd = md5(trim($post['adminPassword']));
        $uid = intval($this->uid);
        
        //admin才有更改权限
        if(trim($this->username) == 'admin'){
            $db = \Config\Database::connect();
            $updateResult = $db->query("UPDATE u_user SET `pwd`=? WHERE `id`=?", [$pwd, $uid]);
            $row = $updateResult->connID->affected_rows;
            if($row < 0){
                exit(json_encode(['code' => 199,'msg' => 'fail'], JSON_UNESCAPED_UNICODE));
            } else {
                exit(json_encode(['code' => 200,'msg' => '更新成功'], JSON_UNESCAPED_UNICODE));
            }
        } else {
            exit(json_encode(['code' => 199,'msg' => '无修改权限'], JSON_UNESCAPED_UNICODE));
        }
    }
}
