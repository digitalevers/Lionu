<?php 

/**
 * 默认控制器
 */
namespace App\Controllers;


class Home extends NotneedloginController{

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
	    header('Location:/static/');
	    exit();
	}
	


	//--------------------------------------------------------------------
    	
}
