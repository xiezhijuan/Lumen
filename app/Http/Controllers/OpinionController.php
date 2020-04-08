<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\Http\Models\Opinion;
use Illuminate\Http\Request;
// 意见反馈
class OpinionController extends Controller
{
    
    /**
     * [index 意见反馈]
     * @return [type] [description]
     */
    public function add()
    {

            $param = $this->requests->getQueryParams();
            $data["user_id"] = empty($param["user_id"]) ? 0 : $param["user_id"] ;
            $data["opinion_content"] = empty($param["opinion_content"]) ? '' : $param["opinion_content"] ;
            $data["phone"] = empty($param["phone"]) ? '' : $param["phone"] ;
            $data["lable"] = empty($param["lable"]) ? '' : $param["lable"] ;
            if(empty($data["user_id"]) || empty($data["opinion_content"])){
                 return returnJson(-1,'缺少参数');
            }
            if(isset($param["img"])){
                $data['img'] = $param["img"];
            }

            
            $data["add_time"] = time();
           $res =   DB::table("lgp_home_opinion")->insert($data);
           if($res){
            return returnJson(2,'success');
          }else{
            return returnJson(-1,'提交失败');
          }
         
         
    }


    /**
     * 图片上传.
     * @param $data 
     *
     * @return int 成功0，失败返回对应的错误码
     */
    public function uploadImg(Request $request){
        if(!empty($_FILES['file'])){
             $file = $_FILES["file"];
             $dir = 'uploads/';
              // $dir = 'static/touxiang/';
             $typeArr = explode("/", $file["type"]);
              
             $new_file = $dir . rand(1,1000) .time() . '.' . $typeArr[1];
              // 移动文件
            if(move_uploaded_file($_FILES["file"]["tmp_name"],$new_file)){

                $imgUrl = 'https://'.$_SERVER['SERVER_NAME']. '/' . $new_file;
                return returnJson(2,'success', $imgUrl);
            }
        }else{
              return returnJson(-1,'未接收到图片信息');

        }
    }


    /**
     * 导师资料上传-图片上传.
     * @param $data 
     *
     * @return int 成功0，失败返回对应的错误码
     */
    public function tutoruploadImg(Request $request){


        if(!empty($_FILES['file'])){
             $file = $_FILES["file"];
             $dir = 'static/';
             $typeArr = explode("/", $file["type"]);
              
             $new_file = $dir . rand(1,1000) .time() . '.' . $typeArr[1];
              // 移动文件
            if(move_uploaded_file($_FILES["file"]["tmp_name"],$new_file)){

                $imgUrl = 'https://'.$_SERVER['SERVER_NAME']. '/' . $new_file;
                // 查询导师
                $author_res = objectToArray(DB::table('lgp_home_tutor')->where('id',$request->input('author_id'))->first());
                if ($author_res['tutor_education_prove'] == '') {
                    $author_res_tutor_education_prove = $imgUrl;
                }else{
                    $author_res_tutor_education_prove = ',' . $imgUrl;
                }

                $author_update_res = DB::table('lgp_home_tutor')->where('id',$request->input('author_id'))->update(['tutor_education_prove' => $author_res_tutor_education_prove]);

                if ($author_update_res) {
                    return returnJson(2,'success',[]);
                }
                return returnJson(-1,'保存失败',[]);
            }
        }else{
              return returnJson(-1,'未接收到图片信息');

        }
    }




  
}
