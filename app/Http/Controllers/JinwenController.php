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
 * Class 名鸽堂
 * @package App\Http\Controllers
 */
class JinwenController extends Controller
{
   public static  $documentCon = [
               4=>'头脑风暴',
                5=>'撰写文书',
                6=>'修改文书',
   ];
   public static $wenshuType = [
        'PS',
        'RL',
        'CV',
        'ESSAY(<150字)',
        'ESSAY(151-300)',
        'ESSAY(301-500)',
        'ESSAY(501-700)',
        'ESSAY(>700)',
   ];

    /**
     * [index 锦文殿初始化列表]
     * @param string user_id      用户名id（必传）
     * @param string country_id   国家id（必传）
     * @return [type] [description]
     */
    public function index(){
           $this->is_wenshu = 1;  //调用锦文殿列表
           $data['docServer'] = self::$documentCon;
           $data['docType']  = self::$wenshuType;
           // 学历
           $data['education']  = objectToArray(DB::table('lgp_home_education')->where('status',1)->orderBy("sort",'asc')->get());
           $data['tutor'] =  $this->jinwenList();
           return returnJson(2,'success',$data);
    }


    /**
     * [index 锦文殿导师列表]
     * @param int    user_id      用户名id（必传）
     * @param string country_id   国家id（必传）
     * @param string keyword      搜索关键字（导师名字(搜索),学历(搜索),院校(搜索),专业(搜索)  ）        
     * @param string documentCon  服务类型 （修改文书，文书撰写，文书头脑风暴）
     * @param int    wenshu_name  文书名 （ PS，RL，CV ，ESSAY(<150字) 的id）
     * @param int    page         分页
     * @return array [导师信息]
     */
    public function jinwenList(){
        $param = $this->requests->getQueryParams();
        $user_id = empty($param["user_id"]) ? 0 : $param["user_id"] ;
        $country_id = empty($param["country_id"]) ? 1 : $param["country_id"] ;
        // $user_id = 15;
        // $country_id = 1;
        if(empty($user_id)){
             return returnJson(-1,'缺少参数');
        }
        $where = [];
        $whereIn = [];
        // 服务类型如： 修改文书，文书撰写，头脑风暴
        if(isset($param["documentCon"]) && !empty($param["documentCon"])){
            $where[] = ['lgp_home_tutor.tutor_wenshu_title','like','%'.$param["documentCon"].'%'];
        }
        // 阶段id
         if(isset($param["education_id"]) && !empty($param["education_id"])){
            $where[] = ['lgp_home_tutor.education_id','=',$param["education_id"]];
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

         // 导师文书必要条件
         $tutor_where = array(
                "lgp_home_tutor.status" => 1,//是否展示该导师
                "lgp_home_tutor.tutor_checked_status" => 2,//是否审核通过
                "lgp_home_tutor.tutor_is_wenshu" => 1,//是开启文书
                'lgp_home_tutor.tutor_country' => $country_id
                );

        // 文书类型  PS，RL，CV ，ESSAY(<150字) $param["documentype"] = 'ESSAY';  $param['wenshu_name'] = 'RL';
        if(isset($param["wenshu_name"]) && !empty($param["wenshu_name"])){
            $wenshututor  = objectToArray(DB::table('lgp_home_serve_content')
                            ->leftJoin('lgp_home_tutor', 'lgp_home_serve_content.tutor_id', '=', 'lgp_home_tutor.id')
                            ->where($tutor_where)
                            ->where($where)
                            ->where('serve_title','like','%'.$param["wenshu_name"].'%')
                            ->select('tutor_id')
                            ->paginate(self::$pageNum)->toArray());
           $tutor_ids  =  array_column(objectToArray($wenshututor['data']),'tutor_id');
            if(!empty($tutor_ids)){
                $whereIn = $tutor_ids;
            }
        }
        $tutor = Tutor::orderBy('lgp_home_tutor.sort','asc')
                    ->leftJoin('lgp_home_education as e', 'education_id', '=', 'e.id')
                    ->leftJoin('lgp_home_school as s' ,'tutor_school_id', '=', 's.id')
                    ->where($tutor_where)
                    ->where(function($query) use ($whereIn){
                        // 根据导师id
                        if(!empty($whereIn)){
                            $query->whereIn('lgp_home_tutor.id',$whereIn);
                        }
                    })
                    ->where( $keyword)
                    ->select('lgp_home_tutor.tutor_name','lgp_home_tutor.id','lgp_home_tutor.tutor_profile','lgp_home_tutor.tutor_major','lgp_home_tutor.tutor_label','lgp_home_tutor.tutor_help_count','lgp_home_tutor.tutor_introduction','e.education_name','s.school_name','lgp_home_tutor.tutor_is_wenshu','lgp_home_tutor.status','lgp_home_tutor.tutor_country','lgp_home_tutor.tutor_wenshu_price','lgp_home_tutor.tutor_firstS_price','lgp_home_tutor.tutor_second_school_price')
                    ->paginate(self::$pageNum)->toArray();
               
                
            foreach ($tutor['data'] as $key => &$value) {
                $value['tutor_label'] = explode(',', $value['tutor_label']);
                 if(!empty($value['tutor_profile'])){
                    // $value['tutor_profile'] = imgTailor($value['tutor_profile'],100,100);
                    $value['tutor_profile'] = self::$lgp_url.imgTailor($value['tutor_profile'],100,100);
                }
            }

            if (!empty($this->is_wenshu)) {
                return $tutor;
            }
           return returnJson(2,'success',$tutor);

    }
    /**
     * [getprocess 获取服务流程]
     * @param int    serve_id          1:一问一答,2:在线指导，3：远程指导A，4：远程指导B  ,5:预约监理导师，首次沟通\r\n6:帮我监理留学中介,7,头脑风暴，8无线问答，9撰写文书，10修改文书,11:直播院校（1v1）,12:院校问答,13:录取对比问询
     * @return array [服务流程内容]
     */
    public function getprocess(){
            $param = $this->requests->getQueryParams();
            if(!isset($param['serve_type']) && empty($param['serve_type'])){
                return returnJson(-1,'缺少必要参数');
            }

           $service_process = self::$service_process[$param['serve_type']];
            // $service_process =DB::table('lgp_home_serve')->where(['status'=>1,'serve_id'=>$param['serve_id']])->value('service_process');
            return returnJson(2,'success',$service_process);
           
    }

 

   

  
}