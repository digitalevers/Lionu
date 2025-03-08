<?php
namespace App\Models;

class NewsModel extends \CodeIgniter\Model
{
    protected $table = 'tb_users';
    
    public function getNews($slug = false)
    {
        if ($slug === false)
        {
            return $this->findAll();
        }
        
        return $this->asArray()->where(['slug' => $slug])->first();
    }
}

