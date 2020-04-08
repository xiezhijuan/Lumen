<?php

namespace App\Http\Models;


use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
// 广告为模型
class Advert extends Model
{

    protected $table = 'lgp_home_advert';
    protected $primaryKey = 'id';
    
}
