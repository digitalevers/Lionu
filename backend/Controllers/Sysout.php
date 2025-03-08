<?php 
namespace App\Controllers;

//use App\Models\NewsModel;

class Sysout extends BaseController
{
    
    /**
     * 登录接口
     * TODO 使用redis存储token数据
     * 登录时生成token后存入redis并返回给前端
     */
	public function login()
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

	
	/**
	 * 退出接口
	 * TODO 使用redis存储用户token 退出时清除redis中的token数据
	 * 登录时先查询redis中是否有该token 没有则代表该用户未登录 若查询到则对token数据进行解析
	 */
	public function logout(){
	    echo json_encode(['code'=>200,'msg'=>'ok']);
	}
	
	
	/**
	 * 测试代理接口
	 */
	public function testProxy(){
	    ///file_put_contents('./testProxy.log',json_encode($_COOKIE));
	    print_r($_SERVER);
	}
	
	public function testProxy2(){
	    ///file_put_contents('./testProxy.log',json_encode($_COOKIE));
	    echo 'abc';
	}
	
}
