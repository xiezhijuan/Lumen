<?php

namespace App\Http\Models;


use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
// 课程模型
class Course extends Model
{

    protected $table = 'lgp_home_course';
    protected $primaryKey = 'id';
    
}
