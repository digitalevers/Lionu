<?php
namespace App\Controllers;

use CodeIgniter\HTTP\IncomingRequest;

/**
 * Class NeedloginController
 *
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 * Extend this class in any new controllers:
 *     class Home extends BaseController
 *
 * For security be sure to declare any new methods as protected or private.
 *
 * @package CodeIgniter
 */


class NeedloginController extends BaseController
{

    protected $uid = 0;
    protected $username = '';
    
	/**
	 * An array of helpers to be loaded automatically upon
	 * class instantiation. These helpers will be available
	 * to all other controllers that extend BaseController.
	 *
	 * @var array
	 */
	protected $helpers = [];

	/**
	 * Constructor.
	 */
	public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
	{
		// Do Not Edit This Line
		parent::initController($request, $response, $logger);
		$this->checkToken();
		//
		//--------------------------------------------------------------------
		// Preload any models, libraries, etc, here.
		//--------------------------------------------------------------------
		// E.g.:
		// $this->session = \Config\Services::session();
	}
	
	protected function checkToken(){
	    $header = $this->request->getHeader('Token');
	    if (empty($header) || empty($token = $header->getValue())) {
	        echo json_encode(['error' => 'hack']);
	        exit(-1);
	    } else {
	        $tokenDecode = authcode($token,'DECODE');
	        if($tokenDecode){
	            $info = explode('_', $tokenDecode);
	            if(empty($info) || empty($info[0]) || empty($info[1])){
	                echo json_encode(['error' => 'hack']);
	                exit(-1);
	            } else {
	                $this->uid = $info[0];
	                $this->username = $info[1];
	            }
	        } else {
	            echo json_encode(['error' => 'hack']);
	            exit(-1);
	        }
	    }
	}

	protected function switchBidType($bidType){
	    switch ($bidType){
	        case 0:
	            return 'cpc';
	        case 1:
	            return 'oCPC';
	        case 2:
	            return 'cpm';
	        case 3:
	            return 'cpa';
	        case 4:
	            return 'maxClick';
	        case 5:
	            return '最大化转化';   //maxConvert
	        case 6:
	            return 'tcpa';
	        case 7:
	            return '手动CPM';
	        case 8:
	            return '增强cpc';
	        default:
	            return 'cpc';
	    }
	    
	}
}
