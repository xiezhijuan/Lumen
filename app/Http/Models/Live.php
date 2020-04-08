<?php

namespace App\Http\Models;


use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
// 直播预约模型
class Live extends Model
{

    protected $table = 'lgp_home_live_subscribe';
    protected $primaryKey = 'id';
    
}
