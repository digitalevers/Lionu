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
	    
	    if($username == 'admin' && $pwd == '123456'){
	        //登录成功
	        $src_data = 'admin_1';
	        $encode_data = authcode($src_data,'ENCODE');
	        echo json_encode(['code'=>200,'msg'=>'ok','data'=>$encode_data]);
	    } else {
	        //登录失败
	        echo json_encode(['code'=>199,'msg'=>'fail']);
	    }
	}

	
	
	
}
