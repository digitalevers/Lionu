<?php 
namespace App\Controllers;

use CodeIgniter\Controller;

/**
 * 测试SDK域名是否已经正确部署
 * @author root
 *
 */
class Ping extends Controller{
    
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);
        //--------------------------------------------------------------------
        // Preload any models, libraries, etc, here.
        //--------------------------------------------------------------------
        // E.g.:
        // $this->session = \Config\Services::session();
    }
    
	public function index(){
	    echo 'ok';
	    /* try{
	    $c = file_get_contents('http://lionu.aliyun.com/ping/index');
	    dump($c);
	    } catch(\Exception $e){
	        echo $e->getMessage();
	    } */
	}

	//--------------------------------------------------------------------
    	
}
