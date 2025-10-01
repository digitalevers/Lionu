<?php
namespace App\Controllers\CronJob;

class Tools extends \CodeIgniter\Controller {
    
    public function message($to = 'World')
    {
        echo "Hello {$to}!".PHP_EOL;
    }
}
