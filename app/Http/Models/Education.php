<?php

namespace App\Http\Models;


use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
// 学历模型
class Education extends Model
{

    protected $table = 'lgp_home_education';
    protected $primaryKey = 'id';
    
}
