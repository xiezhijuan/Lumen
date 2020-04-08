<?php

namespace App\Http\Models;


use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
// 导师模型
class Tutor extends Model
{

    protected $table = 'lgp_home_tutor';
    protected $primaryKey = 'id';
    
}
