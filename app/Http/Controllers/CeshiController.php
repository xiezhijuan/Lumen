<?php

namespace App\Http\Controllers;

use App\Http\Models\Ceshi;
use Illuminate\Http\Request;
use Psr\Http\Message\ServerRequestInterface;
class CeshiController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * [ceshi 测试数据]
     * @return [type] [description]
     */
    public function ceshi(ServerRequestInterface $request)
    {
        echo "<pre>";

        var_dump('1111111111');
        die;
        // 公共函数 /app/http/function
        print_r(ceshi('<h1>谢老师记得给我推她的微信</h1>'));

        echo "<br>";

        // 模型 /app/http/Models
        print_r(Ceshi::where('user_id',1)->get()->toArray());
    }

    /**
     * @Author   JFY
     * @DateTime 2020-04-08
     * @Describe [发送邮件]
     * @Purpose  [purpose]
     * @param    [param]
     * @return   [type]     [description]
     */
    public function send_email(){
          // 发送邮件
        $email_param = array(
            'type'  =>  '类型',
            'subject'   => '1111', // 主题
            'body'  => "2222", // 内容
            'userArr' => $this->email_user_list, // 用户
            'title' => '3333', // 标题
            'user'  => 444, // 用户
            'mobile'    => 555, // 电话
        );
        $res = \App\Http\Controllers\ToolController::SendMail( $email_param );
        var_dump($res);die;
    }

}
