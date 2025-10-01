<?php
/**
 *与媒体渠道授权帐号相关的数据模型
 */
namespace App\Models;


class MediaAccountModel extends \CodeIgniter\Model
{
    protected $table = 'dev_media_account';
    protected $allowedFields = [
        'accessToken',
        'refreshToken',
        'expire_time',
        'status'
    ];
    
    /**
     * 根据查询条件查询所有的广告帐户
     * @param unknown $where
     * @param string $fields
     * @return unknown
     */
    public function getAccount($app_id = '', $channel_id = ''){
        return $this->where("`app_id`='".$app_id."' AND `channel_id`='".$channel_id."'")->find();
    }
    
    /**
     * 更新帐户表信息
     * @param array $data
     */
    public function updateAccount($data = []){
        $this->save($data);
    }
}