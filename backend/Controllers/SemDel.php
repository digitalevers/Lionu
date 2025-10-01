<?php
/**
 * SEM搜索-删除广告
 */
namespace App\Controllers;

class SemDel extends NeedloginController{
    /**
     * 删除投放帐户
     */
    public function delAccounts(){
        $uid = $this->uid;
        $accountId = $this->request->getPost('id', 'intval', 0);
        $delRes = model('MediaAccountModel')->delAccount(['uid'=>$uid, 'id'=>$accountId]);
        _json(["code"=>200,"msg"=>"ok","data"=>$delRes]);
    }
}