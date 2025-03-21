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
   
    public function modifyDomain(){
        
    }
    
    public function modifyPwd(){
        
    }
}
