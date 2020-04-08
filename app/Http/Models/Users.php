<?php

namespace App\Http\Models;


use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
// 用户模型
class Users extends Model
{

    protected $table = 'lgp_home_users';
    protected $primaryKey = 'user_id';
    
}
