<?php
namespace App\Models;

class ConfigSemModel extends \CodeIgniter\Model
{
    protected $table = 'u_conf_sem';
    
    /**
     * 获取360转化上报密钥
     * 推荐使用统一获取接口
     * 该方法逐步废弃
     * @return unknown
     */
    public function get360Config(){
        return $this->asArray()->where("`conf_key`='360key' OR `conf_key`='360secret' OR `conf_key`='360landpage' OR `conf_key`='360jzqs'")->findAll();
    }
    
    /**
     * 获取百度转化上报密钥
     * 推荐使用统一获取接口
     * 该方法逐步废弃
     * @return unknown
     */
    public function getBaiduConfig(){
        return $this->asArray()->where("`conf_key`='baiduToken' OR `conf_key`='baiduLandpage'")->findAll();
    }
    
    /**
     * 获取Bing UET标签Id
     * @return unknown
     */
    public function getBingConfig(){
        return $this->asArray()->where("`conf_key`='uetid'")->findAll();
    }
    
    /**
     * 获取腾讯广告配置
     * @return unknown
     */
    public function getTxConfig(){
        return $this->asArray()->where("`conf_key`='txAccessToken' OR `conf_key`='txAccountId' OR `conf_key`='txUserActionSetId'")->findAll();
    }
    
    /**
     * 统一获取密钥参数接口
     * @return unknown
     */
    public function getTotalConfig(){
        $_config = $this->asArray()->where("1=1")->findAll();
        $config = array_column($_config, null, 'conf_key');
        return $config;
    }
    
    
}

