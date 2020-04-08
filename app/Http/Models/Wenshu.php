<?php

namespace App\Http\Models;


use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
// 科目模型
class Wenshu extends Model
{

    protected $table = 'lgp_home_wenshu';
    protected $primaryKey = 'wenshu_id';
    
}
