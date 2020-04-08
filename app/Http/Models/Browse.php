<?php

namespace App\Http\Models;


use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
// 历史记录模型
class Browse extends Model
{

    protected $table = 'lgp_home_browse';
    protected $primaryKey = 'id';
    
}
