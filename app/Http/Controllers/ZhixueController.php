<?php
/**
 * Created by PhpStorm.
 * User: jfy
 * Date: 2020/2/26
 * Time: 12:29
 */
namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\Http\Models\Course;
use App\Http\Models\Follow;
use App\Http\Models\Tutor;
use App\Http\Controllers\CelebrityController;

/**
 * Class 知学府
 * @package App\Http\Controllers
 */
class ZhixueController extends Controller
{

    /**
     * [index 之学府初始化]
     * @return [type] [description]
     */
    public function onshow(){
        $param = $this->requests->getQueryParams();
        $country_id = empty($param["country_id"]) ? 1 : $param["country_id"];
        // 初始化
        $this->onshow = 1;
        $return = [];
        $return['teacher_list'] = $this->index();
        $school_list = objectToArray(DB::table('lgp_home_school')->where([ ['status',1], ['state','<>',''],  ['country', $country_id] ])->orderBy('sort','asc')->get(['school_name','state','area']));
            $data = [];
            $school_arr = []; 
            $state_arr = [] ;
            $area_arr = [];
            if( !empty($school_list) ){
                foreach ($school_list as $key => $val) {
                    $school_arr[] = $val['school_name'];
                    $state_arr[] = $val['state'];
                    $area_arr[] = $val['area'];
                }
                // 学校数组
                $data['school_arr'] = array_values(array_unique($school_arr));
                // 州数组
                $data['state_arr'] = array_values(array_unique($state_arr));
                // 区数组
                $data['area_arr'] = array_values(array_unique($area_arr));
            }
        $return['data'] = $data;
        return returnJson(2,'success',$return);
    }

    /**
     * [index 之学府列表]
     * @return [type] [description]
     */
    public function index(){

        $param = $this->requests->getQueryParams();
        $country_id = empty($param["country_id"]) ? 1 : $param["country_id"];
        $user_id = empty($param["user_id"]) ? 0 : $param["user_id"];
        // $user_id = 186;
        // $country_id = 2;
        if( empty($user_id) || empty($country_id) ){
            return returnJson(-1,'缺参数');
        }
       
        $keyword = [];
        // 关键字
        if(isset($param["keyword"]) && !empty($param["keyword"])){
            $keyword[] = ['lgp_home_tutor.tutor_name','like','%'.$param["keyword"].'%', 'OR'];
            $keyword[] = ['lgp_home_tutor.tutor_major','like','%'.$param["keyword"].'%', 'OR'];
            $keyword[] = ['lgp_home_tutor.subject_name','like','%'.$param["keyword"].'%', 'OR'];
            $keyword[] = ['e.education_name','like','%'.$param["keyword"].'%', 'OR'];
            $keyword[] = ['s.school_name','like','%'.$param["keyword"].'%', 'OR'];
         }
         $where = [];
          // 查询州
         if(isset($param["state"]) && !empty($param["state"])){
                // $query->Where("s.state" ,'like', "%".$param["state"].'%');
                $where[] = ["s.state" ,'like', "%".$param["state"].'%'];
            }
         // 查询专业
        if(isset($param["school_name"]) && !empty($param["school_name"])){
           // $query->Where("s.school_name" ,'like', "%".$param["school_name"].'%');
            $where[] = ["s.school_name" ,'like', "%".$param["school_name"].'%'];
        }



        // 导师必要条件
         $tutor_where = array(
                "lgp_home_tutor.status" => 1,//是否展示该导师
                "lgp_home_tutor.tutor_checked_status" => 2,//是否审核通过
                "lgp_home_tutor.tutor_is_second_school" => 1,//是秒识鸽校
                'lgp_home_tutor.tutor_country' => $country_id
                );




        $data = Tutor::orderBy('lgp_home_tutor.sort','asc')
                     ->leftJoin('lgp_home_school as s', 'tutor_school_id', '=', 's.id')
                     ->leftJoin('lgp_home_education as e', 'education_id', '=', 'e.id')
                     ->where($tutor_where )
                     ->where($where )
                     ->where($keyword)
                     ->select('lgp_home_tutor.tutor_name', 'lgp_home_tutor.id', 's.school_name', 'lgp_home_tutor.tutor_major', 'lgp_home_tutor.tutor_label', 'lgp_home_tutor.tutor_introduction','lgp_home_tutor.tutor_profile', 'lgp_home_tutor.tutor_follow_count' , 'tutor_second_school_price' ,'lgp_home_tutor.tutor_follow_count', 'lgp_home_tutor.tutor_help_count' ,'e.education_name')
                    ->paginate( self::$pageNum )->toArray();
        

        foreach ($data['data'] as $key => &$val) {
            $val['tutor_label'] = explode(',', $val['tutor_label']);
          if(!empty($val['tutor_profile'])){
                // $value['tutor_profile'] = imgTailor($value['tutor_profile'],100,100);
                $val['tutor_profile'] = self::$lgp_url.imgTailor($val['tutor_profile'],100,100);
            }
        }

        if( isset($this->onshow) ){
            return $data['data'];
            
        }


        return returnJson(2,'success',$data);
    }
     
    /*
        区域，学校联动
    */
    public function link(){
        $param = $this->requests->getQueryParams();
        $country_id = $param['country_id'];

        if( empty($param['user_id']) ){
            return returnJson(-1,'参数有误');
        }
        if( empty($param['state']) && empty($param['school']) ){
            $school_list = objectToArray(DB::table('lgp_home_school')->where([ ['status',1], ['state','<>',''],  ['country', $country_id] ])->orderBy('sort','asc')->get(['school_name','state','area']));

            if( !empty($school_list) ){
                foreach ($school_list as $key => $val) {
                    $school_arr[] = $val['school_name'];
                    $state_arr[] = $val['state'];
                    $area_arr[] = $val['area'];
                }
                // 学校数组
                $data['school_arr'] = array_unique($school_arr);
                // 州数组
                $data['state_arr'] = array_unique($state_arr);
                // 区数组
                $data['area_arr'] = array_unique($area_arr);
            }
            return returnJson(2,'success',$data);
        }

        // 判断用户是否存在
        if( !\App\Http\Controllers\UserController::checkUser( $param['user_id'] ) ){
            return returnJson(-1,'该用户不存在');
        }

        if( !empty($param['state']) ){
            // 返回这个州下面所有的学校
            $school_list = objectToArray(DB::table('lgp_home_school')->where([ ['status',1], ['state',$param['state']], ['country', $country_id] ])->orderBy('sort','asc')->get(['school_name']));
            $data = array_unique(array_column($school_list, 'school_name'));
        }else if( !empty($param['school']) ){
            // 返回这个学校所在的州
            $school_info = objectToArray(DB::table('lgp_home_school')->where([ ['school_name',$param['school']], ['country', $country_id] ])->get(['state'])->first());
            $data = $school_info['state'];
        }

        return returnJson(2,'success',$data);
    }

}