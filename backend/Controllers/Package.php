<?php 
/**
 * 包处理模块
 */
namespace App\Controllers;

//use App\Models\NewsModel;

class Package extends BaseController
{
    
	/**
	 * 根据appid 和 planid 生成Android SDK
	 */
    public function createSDK(){
        try{
            $zip = new \ZipArchive;
            $dest_file = ROOTPATH.'lionu.aar';
            if ($zip->open($dest_file) === TRUE) {
                $conf = array(
                    'host'=>'127.0.0.1',
                    'appid'=>'123',
                    'planid'=>'123'
                );
                $conf = json_encode($conf);
                $zip->addFromString('assets/lion-u-config.json', $conf);
                $zip->close();
                //下载apk包
                //$this->download($dest_file);
            } else {
                exit('打开sdk包失败');
            }
        } catch (\Exception $e){
            echo $e->getMessage();
        }
    }
	
	
}
