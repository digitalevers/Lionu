<?php
/**
 * SEM搜索-编辑广告
 */
namespace App\Controllers;

class SemEdit extends NeedloginController{
    
    private $updateCampaignNeedFields = ['budget','status','media_campaignid','campaign_id','mCampaignOcpcBid','mCampaignBid','campaignOcpcBid','campaignBid','m_ad_ocpc_id','ad_ocpc_id'];
    private $updateAdvGroupNeedFields = ['price','status','media_advgroupid','media_campaignid','advgroup_id'];
    private $updateCreativeNeedFields = ['status','media_advgroupid','media_creativeid','media_creativetype','creative_id'];
    
    /**
     * 修改帐户
     */
    public function updateAccount(){
        $uid = $this->uid;
        $budget = $this->request->getPost('budget', 'intval', 0);
        $account_id = $this->request->getPost('account_id', 'intval', 0);
        $accountModel = model('MediaAccountModel');
        $accounts = $accountModel->getAccountsMap('uid='.$uid.' AND status>=0 AND id='.$account_id);
        if(count($accounts) > 0){
            $accInfo = $accounts[$account_id];
            //$res = $accountModel->setDayBudget($budget, $accountInfo);
            switch ($accInfo['channel_ename']) {
                case 's360':
                    if($budget < 30 || ($budget % 10 != 0)){
                        _json(["code"=>198,"msg"=>"日预算需要大于等于30且为10的倍数"], 1);
                    } else {
                        $resArr = $accountModel->updateAccount360($accInfo, $budget);
                        _json($resArr, 1);
                    }
                    break;
                case 'baidu':
                    if($budget < 50){
                        _json(["code"=>198,"msg"=>"日预算需要大于等于50"], 1);
                    } else {
                        $resArr = $accountModel->updateAccountBaidu($accInfo, $budget);
                        _json($resArr, 1);
                    }
                    break;
                case 'bing':
                    break;
                case 'tencent':
                    break;
                default:
                    break;
            }
        } else {
            _json(["code"=>199,"msg"=>"account id error"]);
        }
        _json(["code"=>200,"msg"=>"ok"]);
    }
    
    /**
     * 修改广告计划
     */
    public function updateCampaign(){
        $uid = $this->uid;
        $account_id = $this->request->getPost('account_id', 'intval', 0);
        $media_campaignid = $this->request->getPost('media_campaignid', 'trim|xss_clean|strip_tags', '');
        $campaign_id = $this->request->getPost('campaign_id', 'intval', 0);
        //TODO 投放地段 投放时间
        $budget = $this->request->getPost('budget', 'intval', null);
        $status = $this->request->getPost('status', 'intval', null);
        $campaignBid = $this->request->getPost('campaign_bid', 'floatval', null);
        $mCampaignBid = $this->request->getPost('m_campaign_bid', 'floatval', null);
        
        $campaignOcpcBid = $this->request->getPost('ocpc_bid', 'floatval', null);               //pc ocpc出价
        $mCampaignOcpcBid = $this->request->getPost('m_ocpc_bid', 'floatval', null);            //移动 ocpc出价
        $ad_ocpc_id = $this->request->getPost('ad_ocpc_id', 'trim|xss_clean|strip_tags', '');
        $m_ad_ocpc_id = $this->request->getPost('m_ad_ocpc_id', 'trim|xss_clean|strip_tags', '');
        
        $accountModel = model('MediaAccountModel');
        $campaignModel = model('MediaCampaignModel');
        $accounts = $accountModel->getAccountsMap('uid='.$uid.' AND status>=0 AND id='.$account_id);
        if(count($accounts) > 0){
            $accInfo = $accounts[$account_id];
            $updateCampaignBody = [];
            foreach ($this->updateCampaignNeedFields as $v){
                if(isset(${$v})){
                    $updateCampaignBody[$v] = ${$v};
                }
            }
            switch ($accInfo['channel_ename']){
                case 's360':
                    $res = $campaignModel->updateCampaign360($accInfo, $updateCampaignBody);
                    if(isset($res['failures'])){
                        _json(['code'=>199,'msg'=>$res['failures'][0]['message']], 1);
                    } else {
                        _json(['code'=>200,'msg'=>'ok'], 1);
                    }
                    break;
                case 'baidu':
                    $res = $campaignModel->updateCampaignBaidu($accInfo, $updateCampaignBody);
                    if(count($res['header']['failures']) > 0){
                        _json(['code'=>199,'msg'=>$res['header']['failures'][0]['message']], 1);
                    } else {
                        _json(['code'=>200,'msg'=>'ok'], 1);
                    }
                    break;
                case 'bing':
                    $res = $campaignModel->updateCampaignBing($accInfo, $updateCampaignBody);
                    if(isset($res['PartialErrors']) && count($res['PartialErrors']) > 0){
                        _json(['code'=>199,'msg'=>$res['PartialErrors'][0]['Message']], 1);
                    } else {
                        _json(['code'=>200,'msg'=>'ok'], 1);
                    }
                    break;
                default:
                    break;
            }
        } else {
            _json(['code'=>199,'msg'=>'error request']);
        }
    }
    
    /**
     * 修改广告组
     */
    public function updateAdvGroup(){
        $uid = $this->uid;
        $account_id = $this->request->getPost('account_id', 'intval', 0);
        $media_advgroupid = $this->request->getPost('media_advgroupid', 'trim|xss_clean|strip_tags', '');
        $media_campaignid = $this->request->getPost('media_campaignid', 'trim|xss_clean|strip_tags', '');
        $advgroup_id = $this->request->getPost('advgroup_id', 'intval', 0);
        
        //TODO 投放地段 投放时间
        $price = $this->request->getPost('price', 'floatval', 0);
        $status = $this->request->getPost('status', 'intval', null);
        
        $accountModel = model('MediaAccountModel');
        $advGroupModel = model('MediaAdvGroupModel');
        $accounts = $accountModel->getAccountsMap('uid='.$uid.' AND status>=0 AND id='.$account_id);
        if(count($accounts) > 0){
            $accInfo = $accounts[$account_id];
            $updateAdvGroupBody = [];
            foreach ($this->updateAdvGroupNeedFields as $v){
                if(isset(${$v})){
                    $updateAdvGroupBody[$v] = ${$v};
                }
            }
            switch ($accInfo['channel_ename']){
                case 's360':
                    $res = $advGroupModel->updateAdvGroup360($accInfo, $updateAdvGroupBody);
                    if(isset($res['failures'])){
                        _json(['code'=>199,'msg'=>$res['failures'][0]['message']], 1);
                    } else {
                        _json(['code'=>200,'msg'=>'ok'], 1);
                    }
                    break;
                case 'baidu':
                    $res = $advGroupModel->updateAdvGroupBaidu($accInfo, $updateAdvGroupBody);
                    if(count($res['header']['failures']) > 0){
                        _json(['code'=>199,'msg'=>$res['header']['failures'][0]['message']], 1);
                    } else {
                        _json(['code'=>200,'msg'=>'ok'], 1);
                    }
                    break;
                case 'bing':
                    $res = $advGroupModel->updateAdvGroupBing($accInfo, $updateAdvGroupBody);
                    if(isset($res['PartialErrors']) && count($res['PartialErrors']) > 0){
                        _json(['code'=>199,'msg'=>$res['PartialErrors'][0]['Message']], 1);
                    } else {
                        _json(['code'=>200,'msg'=>'ok'], 1);
                    }
                    break;
                default:
                    break;
            }
        } else {
            _json(['code'=>199,'msg'=>'error request']);
        }
    }
    
    /**
     * 修改创意
     */
    public function updateCreative(){
        $uid = $this->uid;
        $account_id = $this->request->getPost('account_id', 'intval', 0);
        $media_creativeid = $this->request->getPost('media_creativeid', 'trim|xss_clean|strip_tags', '');
        $media_creativetype = $this->request->getPost('media_creativetype', 'trim|xss_clean|strip_tags', '');
        $media_advgroupid = $this->request->getPost('media_advgroupid', 'trim|xss_clean|strip_tags', '');
        $creative_id = $this->request->getPost('creative_id', 'intval', 0);
        
        $status = $this->request->getPost('status', 'intval', null);
        
        $accountModel = model('MediaAccountModel');
        $creativeModel = model('MediaCreativeModel');
        $accounts = $accountModel->getAccountsMap('uid='.$uid.' AND status>=0 AND id='.$account_id);
        if(count($accounts) > 0){
            $accInfo = $accounts[$account_id];
            $updateCreativeBody = [];
            foreach ($this->updateCreativeNeedFields as $v){
                if(isset(${$v})){
                    $updateCreativeBody[$v] = ${$v};
                }
            }
            switch ($accInfo['channel_ename']){
                case 's360':
                    $res = $creativeModel->updateCreative360($accInfo, $updateCreativeBody);
                    if(count($res['failures']) > 0){
                        _json(['code'=>199,'msg'=>$res['failures']['message']], 1);
                    } else {
                        _json(['code'=>200,'msg'=>'ok'], 1);
                    }
                    break;
                case 'baidu':
                    $res = $creativeModel->updateCreativeBaidu($accInfo, $updateCreativeBody);
                    if(count($res['header']['failures']) > 0){
                        _json(['code'=>199,'msg'=>$res['header']['failures'][0]['message']], 1);
                    } else {
                        _json(['code'=>200,'msg'=>'ok'], 1);
                    }
                    break;
                case 'bing':
                    $res = $creativeModel->updateCreativeBing($accInfo, $updateCreativeBody);
                    if(isset($res['OperationErrors']) && count($res['OperationErrors']) > 0){
                        _json(['code'=>199,'msg'=>$res['OperationErrors'][0]['Details']], 1);
                    } else {
                        _json(['code'=>200,'msg'=>'ok'], 1);
                    }
                    break;
                default:
                    break;
            }
        } else {
            _json(['code'=>199,'msg'=>'error request']);
        }
    }
}