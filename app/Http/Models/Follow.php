<?php

namespace App\Http\Models;


use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
// 我的关注模型
class Follow extends Model
{

    protected $table = 'lgp_home_follow';
    protected $primaryKey = 'id';
    
}
