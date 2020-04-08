<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\Http\Models\Video;

use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

// 分享
class ShareController extends Controller
{
    public $getImg = "http://admin.highschool86.com";
    public $inputImg = "https://www.highschool86.com";
  

    
 /** 
     * 改变图片的宽高 
     *  
     * @author flynetcn (2009-12-16) 
     *  
     * @param string $img_src 原图片的存放地址或url  
     * @param string $new_img_path  新图片的存放地址  
     * @param int $new_width  新图片的宽度  
     * @param int $new_height 新图片的高度 
     * @return bool  成功true, 失败false 
     */  
    public function resize_image($img_src, $new_img_path, $new_width, $new_height)  
    {  
        $img_info = @getimagesize($img_src);  
        
        if (!$img_info || $new_width < 1 || $new_height < 1 || empty($new_img_path)) {  
            return false;  
        }  

        if (strpos($img_info['mime'], 'jpeg') !== false) {  
            $pic_obj = imagecreatefromjpeg($img_src);  
        } else if (strpos($img_info['mime'], 'gif') !== false) {  
            $pic_obj = imagecreatefromgif($img_src);  
        } else if (strpos($img_info['mime'], 'png') !== false) {  
            $pic_obj = imagecreatefrompng($img_src);  
        } else {  
            return false;  
        }  


        $pic_width = imagesx($pic_obj);  
        $pic_height = imagesy($pic_obj);  
        if (function_exists("imagecopyresampled")) {  
            $new_img = imagecreatetruecolor($new_width,$new_height);  
            imagecopyresampled($new_img, $pic_obj, 0, 0, 0, 0, $new_width, $new_height, $pic_width, $pic_height);  
        } else {  
            $new_img = imagecreate($new_width, $new_height);  
            imagecopyresized($new_img, $pic_obj, 0, 0, 0, 0, $new_width, $new_height, $pic_width, $pic_height);  
        }  
       
        if (preg_match('~.([^.]+)$~', $new_img_path, $match)) {  
            $new_type = strtolower($match[1]);  
            switch ($new_type) {  
                case 'jpg':  
                    imagejpeg($new_img, $new_img_path);  
                    break;  
                case 'gif':  
                    imagegif($new_img, $new_img_path);  
                    break;  
                case 'png':  
                    imagepng($new_img, $new_img_path);  
                    break;  
                default:  
                    imagejpeg($new_img, $new_img_path);  
            }  
        } else {  
            imagejpeg($new_img, $new_img_path);  
        }  
        imagedestroy($pic_obj);  
        imagedestroy($new_img);  
        return true;  
    }

     /*
    *   转换字体
    */
    public static function to_entities($string)
    {
        $len = strlen($string);
        $buf = "";
        for($i = 0; $i < $len; $i++){
            if (ord($string[$i]) <= 127){
                $buf .= $string[$i];
            } else if (ord ($string[$i]) <192){
                //unexpected 2nd, 3rd or 4th byte
                $buf .= "&#xfffd";
            } else if (ord ($string[$i]) <224){
                //first byte of 2-byte seq
                $buf .= sprintf("&#%d;",
                    ((ord($string[$i + 0]) & 31) << 6) +
                    (ord($string[$i + 1]) & 63)
                );
                $i += 1;
            } else if (ord ($string[$i]) <240){
                //first byte of 3-byte seq
                $buf .= sprintf("&#%d;",
                    ((ord($string[$i + 0]) & 15) << 12) +
                    ((ord($string[$i + 1]) & 63) << 6) +
                    (ord($string[$i + 2]) & 63)
                );
                $i += 2;
            } else {
                //first byte of 4-byte seq
                $buf .= sprintf("&#%d;",
                    ((ord($string[$i + 0]) & 7) << 18) +
                    ((ord($string[$i + 1]) & 63) << 12) +
                    ((ord($string[$i + 2]) & 63) << 6) +
                    (ord($string[$i + 3]) & 63)
                );
                $i += 3;
            }
        }
        return $buf;
    }

    /**
     * 海报字体换行
     * @param  [type] $fontsize [字体大小]
     * @param  [type] $angle    [角度]
     * @param  [type] $fontface [字体名称]
     * @param  [type] $string   [字符串]
     * @param  [type] $width    [预设宽度]
     * @return [type]           [str]
     */
    public function autowrap($fontsize, $angle, $fontface, $string, $width) {
        // 这几个变量分别是 字体大小, 角度, 字体名称, 字符串, 预设宽度
        $content = "";

        // 将字符串拆分成一个个单字 保存到数组 letter 中
        for ($i=0;$i<mb_strlen($string);$i++) {
            $letter[] = mb_substr($string, $i, 1);
        }

        foreach ($letter as $l) {
            $teststr = $content." ".$l;
            $teststr =$this->to_entities($teststr);
            $testbox = imagettfbbox($fontsize, $angle, $fontface, $teststr);
            // 判断拼接后的字符串是否超过预设的宽度
            if (($testbox[2] > $width) && ($content !== "")) {
                $content .= "\n";
            }
            $content .= $l;
        }
        return $content;
    }

    

    // 将图片转换为圆形
    public function yuan_img($imgUrl,$new_img_path) {
        // ini_set ( 'default_socket_timeout', 1 );
        if (!$imgUrl ||  empty($new_img_path)) {  
            return false;  
        }  
        // 改变图片大小
        $res =  $this->resize_image($imgUrl,$new_img_path,395,240);//调用上面的函数
         if ($res ) {
            $imgUrl = $new_img_path;
        }
        $wh = @getimagesize ( $imgUrl );
        $w = $wh [0];
        $h = $wh [1];
      
        $src_img = imagecreatefromstring ( file_get_contents ( $imgUrl ) );

        $img = imagecreatetruecolor ( $w, $h );
        imagesavealpha ( $img, true );
        //拾取一个完全透明的颜色,最后一个参数127为全透明
        $bg = imagecolorallocatealpha ( $img, 255, 255, 255, 127 );

        imagefill ( $img, 0, 0, $bg );

        $w2 = min ( $w, $h );
        $h2 = $w;
        $r = $w2 / 2; //圆半径
        if ($w < $h) { //宽大于高
            $y_x = $r; //圆心X坐标
            $y_y = $h / 2; //圆心Y坐标
        } else { //宽小于高
            $y_x = $w / 2; //圆心X坐标
            $y_y = $r; //圆心Y坐标
        }
        for($x = 0; $x < $w; $x ++) {
            for($y = 0; $y < $h; $y ++) {
                $rgbColor = imagecolorat ( $src_img, $x, $y ); //获取指定位置像素的颜色索引值
                if (((($x - $y_x) * ($x - $y_x) + ($y - $y_y) * ($y - $y_y)) < ($r * $r))) {
                    imagesetpixel ( $img, $x, $y, $rgbColor ); //在指定的坐标绘制像素
                }
            }
        }
        //1.创建画布
        $im = imagecreatetruecolor ( 598,386);
       
        //2.上色
        $color = imagecolorallocate ( $im, 255, 255, 255 );
        //3.设置透明
        imagecolortransparent ( $im, $color );
        imagefill ( $im, 0, 0, $color );
        //   header ( "content-type:image/png" );
        // imagepng ( $img);
        // die;
        // 将圆形图片放在画布中间  计算求出画布长和宽的中间值在减去圆半径则为开始坐标
        //  $r*2  实际计算为 $h2-($h2-$r*2);
        imagecopy ( $im, $img, 598/2-$r, 386/2-$r, ($w / 2) - $r, 0, $w2, $r*2);
       
        imagejpeg($im, $new_img_path);
        imagedestroy($im); 

        return $new_img_path; 
    }





  



     /**
     * 分享缩略图
     * @return [type] [description] 1文章/2点播/3直播/4小视频/5 导师
     */
    public function index()
    {   
        $param = $this->requests->getQueryParams();
        // $param["type"] = 2;  
        // $param["id"] = 7;  
        // $param["type"] = 1;  
        // $param["id"] = 4; 
        if($param["type"] == 1 ){ //文章
            $video_res = objectToArray(DB::table("lgp_home_article")->where("id",$param["id"])->select("article_img as img",'id',"article_name as title")->first());
        }else if($param["type"] == 4){ //小视频
               $video_res = objectToArray(DB::table("lgp_home_video")->where("id",$param["id"])->select("img",'id',"title")->first());
        }else if($param['type'] == 2|| $param['type'] == 3){
             $video_res = objectToArray(DB::table("lgp_home_course")->where("id",$param["id"])->select("class_title_img as img",'id',"class_name as title",'live_introduction as describe')->first());
        }

        // 调整完之后测试
        $t_data['erweima_dizhi'] = $this-> getwxacode($param);
        $t_new_width = $this-> resize_image($t_data['erweima_dizhi'],'./static/fenxiang/erweima_dizhi.jpeg',154,154);
        if ($t_new_width ) {
            unlink($t_data['erweima_dizhi']);
            $t_data['erweima_dizhi'] = './static/fenxiang/erweima_dizhi.jpeg';
        }

        // 生成视频封面图 大小  (文章，直播，点播 , 小视频)
        if ($param['type'] == 1 || $param['type'] == 2|| $param['type'] == 3 || $param['type'] == 4) { 
            // 查询系列课
            $t_data['thumbnail_dizhi'] = $this->getImg.$video_res['img'];
        }

        // $t_data["thumbnail_dizhi"] = $this->yuan_img( $t_data['thumbnail_dizhi'],'./static/fenxiang/thumbnail_dizhi.jpeg',598,386);
        if($param['type'] == 5){ //导师生成圆图
             $t_data["thumbnail_dizhi"] = $this->yuan_img( $t_data['thumbnail_dizhi'],'./static/fenxiang/thumbnail_dizhi.jpeg',598,386);
        }else{
            $t_new_width = $this-> resize_image( $t_data["thumbnail_dizhi"] ,'./static/fenxiang/thumbnail_dizhi.jpeg',598,386);
            if ($t_new_width ) {
                $t_data['thumbnail_dizhi'] = './static/fenxiang/thumbnail_dizhi.jpeg';
            }
        }
        // 生成视频封面图 大小 结束

        // 生成标题文字图片==================开始================
        // 字体大小
         $size = 26;
        //字体类型，本例为宋体 提交时要修改 (文章，直播，点播 , 小视频)
         $font = "/usr/share/fonts/simsun.ttc";
         if ($param['type'] == 1 || $param['type'] == 2|| $param['type'] == 3 || $param['type'] == 4 ) {
            $text = $video_res['title'];
        }
        $text = $this->autowrap($size,0,$font,$text,315);
        //创建一个长为500高为80的空白图片
        $img = imagecreate(315, 180);
        //给图片分配颜色
        imagecolorallocate($img, 255, 255, 255);
        //设置字体颜色
        $black = imagecolorallocate($img, 0, 0, 0);
        //将ttf文字写到图片中
        $text =$this->to_entities($text);
        imagettftext($img, $size, 0, 0, 30, $black, $font, $text);
        //发送头信息

        //输出文字图片信息
        // header('Content-Type: image/jpeg'); 
        imagejpeg($img,'./static/fenxiang/title.jpeg');
        $t_data['title_dizhi'] = './static/fenxiang/title.jpeg';

       // 生成表提文字图片======结束============================



        // 生成简介文字图片==================开始================
         //字体大小
        $size = 20;
        //字体类型，本例为宋体
        $font = "/usr/share/fonts/simsun.ttc";
        if ($param['type'] == 1 || $param['type'] == 4 || $param['type'] == 2) {
            // 查询系列课
            $text = '钱包既然支撑不起你的财富,就别让知识再贫穷你的人生。打开视频,再充会电!';

        }else{
            //显示的文字
            $text = $video_res['describe'];
            if(empty($text)){
                $text = '钱包既然支撑不起你的财富,就别让知识再贫穷你的人生。打开视频,再充会电!';
            }
        }

        $text = $this->autowrap($size,0,$font,$text,518);
        //创建一个长为500高为80的空白图片
        $img = imagecreate(518, 100);
        //给图片分配颜色
        imagecolorallocate($img, 255, 255, 255);
        //设置字体颜色
        $black = imagecolorallocate($img, 0, 0, 0);
        //将ttf文字写到图片中
        
        $text =$this->to_entities($text);
        imagettftext($img, $size, 0, 0, 25, $black, $font, $text);
        //发送头信息
        //输出图片
        // header('Content-Type: image/jpeg'); 
        imagejpeg($img,'./static/fenxiang/video_describe.jpeg');
        $t_data['video_describe_dizhi'] = './static/fenxiang/video_describe.jpeg';

        // 生成简介文字图片==================结束================

         //原始图像=========开始============================
        $dst = "./static/fenxiang/background.png";
        $dst_im = imagecreatefrompng($dst);
        $dst_info = getimagesize($dst);
        //水印图像
        $src = $t_data['erweima_dizhi'];
        
        $src_im = imagecreatefromjpeg($src);
        $src_info = getimagesize($src);
        // 封面图
        $fengmian = $t_data['thumbnail_dizhi'];
        $fengmian_im = imagecreatefromjpeg($fengmian);
        $fengmian_info = getimagesize($fengmian);
        // 标题
        $title = $t_data['title_dizhi'];
        $title_im = imagecreatefromjpeg($title);
        $title_info = getimagesize($title);
        // // 简介
        $video_describe = $t_data['video_describe_dizhi'];
        $video_describe_im = imagecreatefromjpeg($video_describe);
        $video_describe_info = getimagesize($video_describe);
         //合并二维码
        imagecopymerge($dst_im,$src_im,124,855,0,0,$src_info[0],
        $src_info[1],100);
        // 合并缩略图
        imagecopymerge($dst_im,$fengmian_im,76,80,0,0,$fengmian_info[0],
        $fengmian_info[1],100);

         // 合并title
        imagecopymerge($dst_im,$title_im,218,508,0,0,$title_info[0],
        $title_info[1],100);

        // // 合并简介
        imagecopymerge($dst_im,$video_describe_im,117,650,0,0,$video_describe_info[0],
        $video_describe_info[1],100);

        //输出合并后水印图片
        $time = time();
        imagepng($dst_im,'/home/www/www.highschool86.com/public/static/fenxiang/'.$time.'.png');

        $res['thumbnail'] = 'https://www.highschool86.com/static/fenxiang/'.$time.'.png';
        // $res['thumbnail'] = 'https://xwww.ixlzj.com/static/index/fenxiang/'.$time.'.png';
        return returnJson(2,'生成分享缩略图成功',$res);

      
    }


   
    /**
    * 生成二维码
    *@param
    *@param
    *@return 
    */
    public function getXchCode(){
        // $param = array("type"=>6);
        $param = $this->requests->getQueryParams();
        $param['type'] = 1;
        $res = $this->getwxacode($param);
        return 'https://www.highschool86.com/'.$res;
    }




     //生成二维码 1文章/2点播/3直播/4小视频/5 导师 /6 彬彬教育
    public function getwxacode($param){
        $url = "https://api.weixin.qq.com/wxa/getwxacode?";
        $url .= "access_token=" . $this->getToken();
        // 判断是否为系列课
        if ($param['type'] == 1) {
            // $srt = "/pages/essay_cet/essay_cet?id=".$param['id'];
            $srt = "/packageB/pages/ArticleDetails/ArticleDetails?id=".$param['id'];


        }else if($param['type'] == 2){ //点播 跳转到精品课堂（有导师包含 点播和直播）
            // $srt = "/pages/Boutique/Boutique";
            $srt = "/packageB/pages/VideoShare/VideoShare?id=".$param['id'];

        }else if($param['type'] == 3){ //直播
            // $srt = "/pages/video_cet/video_cet?id=".$param['id'];
            $srt = "/packageB/pages/ElderSister/ElderSister?id=".$param['id'];
        }else if($param['type'] == 4){ //小视频 跳到小视频列表
            $srt = "/pages/Small/Small";
        }else if($param['type'] == 6){
            $srt = "/pages/index/index?type=bbjy";
        }



        $postdata = [
            "path" => $srt
        ];


        $res = $this->curl_post($url,json_encode($postdata),$options=array());
   
        $img = 'static/fenxiang/'.time().'.jpeg';
        $r = file_put_contents($img,$res);
        return './'.$img ;
    }


    //发送获取token请求,获取token(2小时)
    public function getToken() {
        // 判断token是否保存存在
        if (empty(Cache::get('access_token'))) {
            $url = $this->getTokenUrlStr();
            $res = $this->curl_post($url,$postdata='',$options=array());
            $data = json_decode($res,JSON_FORCE_OBJECT);
            Cache::put('access_token',$data['access_token'],10);
            
            return $data['access_token'];
        }else{
             return Cache::get('access_token');
        }
    }


     /**
     * [getTokenUrlStr 获取token的url参数拼接]
     * @return [type] [description]
     */
    public function getTokenUrlStr()
    {
        $getTokenUrl = "https://api.weixin.qq.com/cgi-bin/token?"; //获取token的url
        $WXappid     =  $this->appid; //APPID
        $WXsecret    = $this->secret; //secret
        $str  = $getTokenUrl;
        $str .= "grant_type=client_credential&";
        $str .= "appid=" . $WXappid . "&";
        $str .= "secret=" . $WXsecret;
        return $str;
    }

     /**
     * [curl_post description]
     * @param  string $url      [description]
     * @param  [type] $postdata [description]
     * @param  array  $options  [description]
     * @return [type]           [description]
     */
    public function curl_post($url='',$postdata,$options=array()){
        $ch=curl_init($url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch,CURLOPT_POST,1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        if(!empty($options)){
            curl_setopt_array($ch, $options);
        }
        $data=curl_exec($ch);
        curl_close($ch);
        return $data;
    }


    /**
     * [temIndex 发送模板]
     * @param  string openid      [用户openid]
     * @param  array  fromId      [fromid]
     * @return [type] [description]
     */
    public function formanualTem()
    {
        $param = $this->requests->getQueryParams();
        $openid = isset($param['openid']) ? trim($param['openid']) : ""  ;
        $fromId = isset($param['fromId']) ? trim($param['fromId']) : ""  ;
        $type = isset($param['type']) ? intval($param['type']) : ""  ;
        if(empty($openid) ||  empty($fromId)){
            return returnJson(-1,'缺少参数');
        }
        $data  = '';
         //直播预约提醒
        if($type == 1){ 
            // if(!isset($param["class_id"]) &&empty($param["class_id"])){
            //      return returnJson(-1,'缺少课程id');
            // }
             // $class = objectToArray( 
             //            DB::table("lgp_home_course")
             //            ->leftJoin('lgp_home_tutor as t', 'lgp_home_course.tutor_id', '=', 't.id')
             //             ->select('lgp_home_course.class_name','t.tutor_name','lgp_home_course.live_time','lgp_home_course.live_end')
             //            ->where("lgp_home_course.id",$param["class_id"])->first());
             // if(empty($class)){
             //     return returnJson(-1,'未查到该课程，请核实数据');
             // }

             // if(empty($class["live_time"]) || empty($class["live_end"])){
             //         return returnJson(-1,'课程开始时间或结束时间为空');
             // }  

             // $count =  DB::table("lgp_home_live_subscribe")->where("class_id",$param["class_id"])->count();
             // $date7 = date("Y-m-d",$class["live_time"]);
             // $stime = date('H:i:s',$class["live_time"]);
              $data = '{
                  "touser":"'.$openid.'",
                  "template_id":"6OUkyiLHXLd_4hDFTadAWgtmPDlszdBttRucwDcnJr0",
                  "form_id":"'.$fromId.'",   
                  "data": {
                      "keyword1": {
                          "value":"课程直播提醒",
                          "color": "#FF0300"
                      },
                      "keyword2": {
                          "value":"点击进入直播👇👇👇",
                          "color": "#FD503F"
                      }
                  },
                  "emphasis_keyword": "keyword1.DATA",
                  "page":"/pages/index/index"
                }';

            
        }else if($type == 2){ //预约咨询推送

              $data = '{
                  "touser":"'.$openid.'",
                  "template_id":"6OUkyiLHXLd_4hDFTadAWgtmPDlszdBttRucwDcnJr0",
                  "form_id":"'.$fromId.'",   
                  "data": {
                      "keyword1": {
                          "value":"预约咨询提醒",
                          "color": "#FF0300"
                      },
                      "keyword2": {
                          "value":"您的预约咨询即将开始",
                          "color": "#FD503F"
                      }
                  },
                  "emphasis_keyword": "keyword1.DATA",
                  "page":"/pages/index/index"
                }';

        }
           
        if(empty($data)){
            return returnJson(-1,'数据为空');
        }

        $url = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?';
        $url .= "access_token=" . $this->getToken();

        $result = $this->httpRequest($url,$data);
        $res = json_decode($result,true);
        if($res["errcode"] == 0){
            return returnJson(2,'success');
        }else{
            return returnJson(-1,'推送失败errcode='.$res["errcode"].'errmsg='.$res["errmsg"]);
        }
        return $result;
    }
     /**
     * [order_reply_push 订单回复推送]
     * @param  [type] $order_id    [订单id]
     * @param  string $time   [回复时间 格式 时间戳]
     * @return [type]         [description]
     */
     public function order_reply_push(){
             $param = $this->requests->getQueryParams();
             $time = isset($param['time']) ? intval($param['time']) : time();
             // $param["order_id"] = 63;
             if(!isset($param["order_id"]) && empty($param["order_id"])){
                 return returnJson(-1,'缺少课程id');
             }

             $template_id = 'bg8DcqgNDbWwPwxmWj8zAcB8Db-AnX0KamhW9ErvFsk';
             // $url = '/packageA/pages/orderdetail/orderdetail?jump=1&id=120';
             $url = '/packageA/pages/orderdetail/orderdetail?jump=1&id='.$param["order_id"];
             $order = objectToArray( 
                         DB::table("lgp_home_order as o")
                         ->leftJoin('lgp_home_tutor as t', 'o.tutor_id', '=', 't.id')
                         ->leftJoin('lgp_home_users as u', 'o.user_id', '=', 'u.user_id')
                         ->where("o.order_id",$param["order_id"])
                         ->select('t.tutor_name','o.order_sn','u.openid')
                         ->first());
             if(empty($order)){
                 return returnJson(-1,'未找到订单信息！');
             }
             if(empty($order['openid'])){
                 return returnJson(-1,'openid为空！');

             }
            $name3 = trim($order['tutor_name']);
            $date5 = date("Y-m-d H:i:s",$time);
            $thing4 = '已回复';
            $data=array(
                    'name3'=>array('value'=>$name3),
                    'date5'=>array('value'=>$date5),
                    'thing4'=>array('value'=> $thing4 ),
                );
           
            $params1=array(
                 "touser"=>trim($order['openid']),
                 "template_id"=>$template_id,
                 "page"=>$url,
                 "data"=>$data
              );

            $json_template = json_encode($params1);
            $url = 'https://api.weixin.qq.com/cgi-bin/message/subscribe/send?';
            $url .= "access_token=" . $this->getToken();
            $result = $this->httpRequest($url,$json_template);
            $res = json_decode($result,true);
            if($res["errcode"] == 0){
                return returnJson(2,'success');
            }else{
                return returnJson(-1,'推送失败errcode='.$res["errcode"].'errmsg='.$res["errmsg"]);
            }


     }



    /**
     * [httpRequest description]
     * @param  [type] $url    [description]
     * @param  string $data   [description]
     * @param  string $method [description]
     * @return [type]         [description]
     */
    public function httpRequest($url, $data='', $method='POST'){
        $curl = curl_init();  
        curl_setopt($curl, CURLOPT_URL, $url);  
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);  
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);  
        // curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);  
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);  
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1);  
        if($method=='POST')
        {
            curl_setopt($curl, CURLOPT_POST, 1); 
            if ($data != '')
            {
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);  
            }
        }

        curl_setopt($curl, CURLOPT_TIMEOUT, 30);  
        curl_setopt($curl, CURLOPT_HEADER, 0);  
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);  
        $result = curl_exec($curl);  
        curl_close($curl);  
        return $result;
      } 


}
