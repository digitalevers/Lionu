<?php
namespace App\Models;

class ConfigModel extends \CodeIgniter\Model
{
    protected $table = 'u_conf';
    
    public function getDomain()
    {
        return $this->asArray()->where(['conf_key' => 'SDKDOMAIN'])->findColumn('conf_value')[0];
    }
    
    /**
     * 返回1 所有接口会直接请求渠道媒体的marketing api，所有数据都是实时的，但请求有一定耗时，直观感受就是页面刷新不流畅
     * 返回0 所有接口查找数据库。通过定时脚本与marketing api进行数据同步，请求本身很快，但数据会有一定延时
     * @return int
     */
    public function getApiFlag()
    {
        return intval($this->asArray()->where(['conf_key' => 'APIFLAG'])->findColumn('conf_value')[0]);
    }
    
    public function get360Config(){
        return $this->asArray()->where("`conf_key`='360key' OR `conf_key`='360secret' OR `conf_key`='360landpage'")->findAll();
    }
    
    public function getBaiduConfig(){
        return $this->asArray()->where("`conf_key`='baiduToken' OR `conf_key`='baiduLandpage'")->findAll();
    }
    
    public function getBingConfig(){
        return $this->asArray()->where("`conf_key`='uetid'")->findAll(); 
    }
}

