<?php

namespace App\Http\Models;


use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
// 小视频模型
class Video extends Model
{

    protected $table = 'lgp_home_video';
    protected $primaryKey = 'id';
    
}
