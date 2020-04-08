<?php

namespace App\Http\Models;


use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
// 用户咨询表
class Myask extends Model
{

    protected $table = 'lgp_home_myask';
    protected $primaryKey = 'id';
    
}
