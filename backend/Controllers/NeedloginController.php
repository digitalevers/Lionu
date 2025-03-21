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
	        $this->uid = authcode($token,'DECODE');
	    }
	}

}
