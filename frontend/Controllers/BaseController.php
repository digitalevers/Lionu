<?php
namespace App\Controllers;

/**
 * Class BaseController
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

use CodeIgniter\Controller;

class BaseController extends Controller
{

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
		$this->ifReWrite();
		$this->ifInstall();
		//--------------------------------------------------------------------
		// Preload any models, libraries, etc, here.
		//--------------------------------------------------------------------
		// E.g.:
		// $this->session = \Config\Services::session();
	}
	
	/**
	 * 检测webServer Rewrite功能是否可用
	 */
	private function ifReWrite(){
	    $protocol = stripos($_SERVER['SERVER_PROTOCOL'],'https') === true ? 'https://' : 'http://';
	    $url = $protocol.$_SERVER['HTTP_HOST'].'/ping/index';
	    $header_info = get_headers($url);
	    if(stripos($header_info[0],'404') !== false){
	        echo '请检查Web Server的ReWrite模块是否已安装并加载，如果是Apache，请检查<Directory "/var/www/html">配置节点的AllowOverride配置项是否设置为All';
	        exit;
	    }
	}

	/**
	 * 检测是否已安装
	 */
	private function ifInstall(){
		if(!file_exists(ROOTPATH.'installed')){
		    header('Location:/install/index');
		    exit();
		}
	}
}
