<?php
namespace App\Models;

class ConfigModel extends \CodeIgniter\Model
{
    protected $table = 'u_conf';
    
    public function getDomain()
    {
        return $this->asArray()->where(['conf_key' => 'SDKDOMAIN'])->findColumn('conf_value')[0];
    }
    
}

