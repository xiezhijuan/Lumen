<?php

namespace App\Http\Models;


use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
// 用户咨询信息表（用户咨询评价）
class Myaskproblem extends Model
{

    protected $table = 'lgp_home_myask_problem';
    protected $primaryKey = 'id';
    
}
