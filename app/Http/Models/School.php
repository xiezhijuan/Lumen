<?php

namespace App\Http\Models;


use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
// 院校模型
class School extends Model
{

    protected $table = 'lgp_home_school';
    protected $primaryKey = 'id';
    
}
