<?php 
namespace App\Controllers;

//use App\Models\NewsModel;
use CodeIgniter\HTTP\IncomingRequest;

class Api extends BaseController
{
    
	public function index()
	{
		return view('welcome_message');
	}

	//--------------------------------------------------------------------
    //前端接收数据接口定义
    //激活上报接口
    //注册上报接口
    //登录上报接口
    //付费上报接口 
    
	public function active(){
	    //$model = new NewsModel();
	    //dump($model->getNews());
	    $request = service('IncomingRequest');
	    $info = $request->getPost('info');
	    dump($info);
	    
	}
	
	
	
}
