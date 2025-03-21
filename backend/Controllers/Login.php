<?php 
namespace App\Controllers;

//use App\Models\NewsModel;

class Login extends BaseController
{
	public function index()
	{
	    $post = $this->request->getVar(null,FILTER_SANITIZE_MAGIC_QUOTES); //todo
	    $username = isset($post['username']) && !empty(trim($post['username'])) ? trim($post['username']) : exit('username empty');
	    $pwd = isset($post['pwd']) && !empty(trim($post['pwd'])) ? trim($post['pwd']) : exit('pwd empty');
	    //todo用户名密码长度校验
	    
	    $db = \Config\Database::connect();
	    $result = $db->query("SELECT * FROM u_user WHERE `username`=? AND `pwd`=?", [$username, md5($pwd)])->getResultArray();
	    if(count($result) > 0){
	        //登录成功
	        $src_data = $result[0]['id'];
	        $encode_data = authcode($src_data,'ENCODE');
	        echo json_encode(['code'=>200,'msg'=>'ok','data'=>$encode_data]);
	    } else {
	        //登录失败
	        echo json_encode(['code'=>199,'msg'=>'登录失败']);
	    }
	}
}
