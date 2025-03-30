<?php
namespace App\Models;

class ConfigModel extends \CodeIgniter\Model
{
    protected $table = 'u_conf';
    
    public function getDomain()
    {
        return $this->asArray()->where(['conf_key' => 'SDKDOMAIN'])->findColumn('conf_value')[0];
    }
    
    public function get360Config(){
        return $this->asArray()->where("`conf_key`='360key' OR `conf_key`='360secret' OR `conf_key`='360landpage'")->findAll();
    }
}

