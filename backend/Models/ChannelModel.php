<?php
/**
 *与媒体渠道相关的数据模型
 */

namespace App\Models;

class ChannelModel extends \CodeIgniter\Model
{
    protected $table = 'u_channel';
    
    public function getAllChannels(){
        $origin = $this->asArray()->where("1=1")->findAll();
        return array_column($origin, null, 'id');
    }
}