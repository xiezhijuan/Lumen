<?php

namespace App\Http\Models;


use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
// 意见反馈模型
class Opinion extends Model
{

    protected $table = 'lgp_home_opinion';
    protected $primaryKey = 'id';
    
}
