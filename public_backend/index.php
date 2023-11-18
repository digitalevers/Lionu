<?php
/**
 * 判断php环境是否已运行
 */
if ( false ) {
    ?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>错误: 没有启动PHP</title>
</head>
<body class="wp-core-ui">
<p id="logo"><a href="https://lionu.digitalevers.com/">量U</a></p>
<h1>错误: 没有启动PHP</h1>
</body>
</html>
<?php }
/**
 * 解决跨域问题
 */
header('Access-Control-Allow-Origin:*');
header('Access-Control-Allow-Credentials:true');
//header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
header("Access-Control-Allow-Headers: *, X-Requested-With, Content-Type");
header("Access-Control-Allow-Methods: GET, POST, DELETE, PUT, OPTIONS");
//ob_start();
// Valid PHP Version?
$minPHPVersion = '7.2';
if (phpversion() < $minPHPVersion)
{
	die("Your PHP version must be {$minPHPVersion} or higher to run CodeIgniter. Current version: " . phpversion());
}
unset($minPHPVersion);

// Path to the front controller (this file)
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);

// Location of the Paths config file.
// This is the line that might need to be changed, depending on your folder structure.
$pathsPath = realpath(FCPATH . '../backend/Config/Paths.php');
// ^^^ Change this if you move your application folder

/*
 *---------------------------------------------------------------
 * BOOTSTRAP THE APPLICATION
 *---------------------------------------------------------------
 * This process sets up the path constants, loads and registers
 * our autoloader, along with Composer's, loads our constants
 * and fires up an environment-specific bootstrapping.
 */

// Ensure the current directory is pointing to the front controller's directory
chdir(__DIR__);

// Load our paths config file
require $pathsPath;
$paths = new Config\Paths();


// Location of the framework bootstrap file.
$app = require rtrim($paths->systemDirectory, '/ ') . '/bootstrap.php';

/*
 *---------------------------------------------------------------
 * LAUNCH THE APPLICATION
 *---------------------------------------------------------------
 * Now that everything is setup, it's time to actually fire
 * up the engines and make this app do its thang.
 */
try{
    $app->run();
} catch(\Exception $e){
    echo $e->getFile();
    echo $e->getLine();
    echo $e->getMessage();
}
