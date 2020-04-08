<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

//测试
$router->get('/ceshi', 'CeshiController@ceshi');
//测试
$router->get('/ceshi_send_email', 'CeshiController@send_email');
// 我的赞
$router->get('/mypraise', 'MypraiseController@index');
// 点赞
$router->get('/addpraise', 'MypraiseController@spotpraise');
// 取消点赞
$router->get('/cancelpraise', 'MypraiseController@canclepraise');
// 我的浏览记录
$router->get('/browse', 'BrowseController@index');
// 添加浏览addbrowse
$router->get('/browse_addbrowse', 'BrowseController@addbrowse');

// 精品课程（有导师）
$router->get('/course', 'CourseController@index');
$router->get('/course_Play', 'CourseController@Play');
$router->get('/video', 'VideoController@index');


// 我的关注
$router->get('/follow', 'FollowController@index');
// 关注
$router->get('/addFollow', 'FollowController@addFollow');
// 取消点赞cancelFollow
$router->get('/cancelFollow', 'FollowController@cancelFollow');


// 我的预约（咨询列表）
$router->get('/myask_index', 'MyaskController@index');
// 我的预约（预约咨询评价）
$router->get('/myask_evaluate', 'MyaskController@evaluate');
// 预约咨询创建咨询订单
$router->get('/myask_add', 'MyaskController@add');
// 我的预约删除delAsk
$router->get('/myask_delAsk', 'MyaskController@delAsk');

//课程详情
$router->get('/live_details', 'LiveController@details');
//直播预约
$router->get('/live_subscribe', 'LiveController@subscribe');

// 意见反馈
$router->get('/opinion_add', 'OpinionController@add');
// 图片上传uploadImg
$router->post('/opinion_uploadImg', 'OpinionController@uploadImg');
$router->post('/opinion_tutoruploadImg', 'OpinionController@tutoruploadImg');

// 分享
$router->get('/share_index', 'ShareController@index');
// 生成彬彬教育二维码
$router->get('/share_getXchCode', 'ShareController@getXchCode');


// 直播消息消息推送
$router->get('/share_formanualTem', 'ShareController@formanualTem');

//转发
$router->get('/forward_index', 'ForwardController@index');




// 首页
$router->get('/index', 'IndexController@index');
// 首页导师分页
$router->get('/tPaginate', 'IndexController@tutorpPaginate');
// 首页时时提醒
$router->get('/index_play', 'IndexController@play');

// 导师详情
$router->get('/tutor_index', 'TutorController@index');
// 申请成为导师
$router->get('/tutor_applay', 'TutorController@applay');
// 申请导师学历初始化
$router->get('/tutor_education', 'TutorController@education');





$router->get('/course_list', 'CourseController@list');	//学长学姐列表页

// 小灰鸽 2.0 s

$router->get('/index_two', 'IndexController@indextwo');	//首页改变2.0
$router->get('/index_video', 'IndexController@index_video');	// 	首页-海报情报局-单视频 分页
$router->get('/index_article', 'IndexController@article');	//	首页-海盗情报局-文章 分页

$router->get('/index_zixun', 'IndexController@zixun');	//	导师咨询列表页
$router->get('/index_wenshu', 'IndexController@wenshu');	//	文书列表页
$router->get('/index_wenshulist', 'IndexController@wenshulist');	//	文书列表页
$router->get('/index_yuyan', 'IndexController@yuyan');	//	语言培训
$router->get('/index_yuyanlist', 'IndexController@yuyanlist');	//	文书列表页

$router->get('/tutor_wenshu_detail', 'TutorController@wenshu_detail');	//	文书_详情页
$router->get('/tutor_yuyan_detail', 'TutorController@yuyue_detail');	//	语言培训-详情页
$router->get('/tutor_shequn', 'TutorController@shequn');	// 社群

$router->get('/index_sousou', 'IndexController@sousou');	// 总搜索



// 模板消息推送 getAccessToken  WechatController 
$router->get('/sendnews', 'WechatController@sendNews');	// 消息推送
$router->get('/sendtest', 'WechatController@sendNewsTest');	// 消息推送testSendNews
$router->get('/testsend', 'WechatController@testSendNews');	// 消息推送


// getOpenids



// 小灰鸽 2.0 end




// 广告位调整
// $router->get('/video_jumpPath', 'VideoController@jumpPath');



// 普通路由
// $router->get('foo', function () {
//     return 'Hello World';
// });


// 参数路由
// $router->get('posts/{postId}/comments/{commentId}', function ($postId, $commentId) {
// 	
// });
// 
// 
// 可选参数
// $router->get('user[/{name}]', function ($name = null) {
//     return $name;
// });




//获取用户  unionid	openid
$router->get('/userOpenid', 'UserController@userOpenid');
//新
$router->get('/user_userPhone', 'UserController@userPhone');
// 获取用户信息（判断用户是否授权用户信息）
$router->get('/user_userInfo', 'UserController@userInfo');

// 发送手机号，获取验证码
$router->get('/user_code', 'UserController@sendCode');

$router->get('/article_detail', 'ArticleController@detail');
//导师详情
$router->get('/tutor_index', 'TutorController@index');


// 定时（直播预约）
$router->get('/timing_pushSubscribe', 'TimingController@pushSubscribe');

// 定时（咨询预约）
$router->get('/timing_pushMyask', 'TimingController@pushMyask');


// 支付 创建订单
$router->get('/order_create', 'OrderController@create');
// 订单列表
$router->get('/order_list', 'OrderController@order_list');
// 订单详情
$router->get('/order_details', 'OrderController@order_details');
// 订单修改
$router->get('/order_update', 'OrderController@order_update');
// 申请退款
$router->get('/order_refund', 'OrderController@order_refund');
// 支付回调
$router->post('/order_notify', 'OrderController@notify');
// 获取年级列表 
$router->get('/order_grade', 'OrderController@getGrade');
//订单评价 evaluate
$router->get('/order_evaluate', 'OrderController@evaluate');
// 删除订单 delorder
$router->get('/order_remove', 'OrderController@delorder');
// 创建订单页面 导师价格和年级getTutorPrice
$router->get('/order_getPrice', 'OrderController@getTutorPrice');



// 2.0 订单 xie 
$router->get('/order_create_two', 'OrderController@createtwo'); //创建订单 区分订单类型 2020-02-19
$router->get('/teacher_wenshu_detail', 'TeacherController@wenshu_detail'); //文书 导师详情 连文书价格表查询价格  2020-02-21
$router->get('/teacher_yuyue_detail', 'TeacherController@yuyue_detail'); //文书 导师详情 连语言培训表查询价格  2020-02-21
// 
$router->get('/teacher_wenshu_list', 'TeacherController@wenshulist_two');  //文书导师列表 显示文书表中的价格




//----------------------------- 小灰鸽3.0 接口-----------------------------

$router->get('/index_three', 'IndexController@index_three');  //文书导师列表 显示文书表中的价格

//名鸽堂
$router->get('/celebrity_teacher_s', 'CelebrityController@onshow');  //名鸽堂列表初始化
$router->get('/celebrity_teacher_i', 'CelebrityController@index');  //名鸽堂列表
$router->get('/celebrity_teacher_d', 'CelebrityController@detail');  //名鸽堂详情


// 订单
$router->get('/order_info_onshow', 'OrderController@orderinfoShow'); //生成订单之前的信息确认（初始化）
$router->get('/create_three', 'OrderController@createThree'); //创建订单3.0
$router->get('/create_sever', 'OrderController@addServe'); //海外鸽服与留学培新订单3.0
$router->get('/order_list_three', 'OrderController@order_list_three'); //订单列表3.0
$router->get('/order_details_three', 'OrderController@order_details_three'); //订单详情3.0
$router->get('/order_comment', 'OrderController@comment'); //订单评论
$router->get('/order_cancel', 'OrderController@cancel'); //取消订单
$router->get('/order_question', 'OrderController@question'); //订单提问
$router->get('/order_question_list', 'OrderController@questionList'); //问答列表
// 支付回调
$router->post('/order_three_notify', 'OrderController@three_notify');
$router->get('/order_rapidly', 'OrderController@order_rapidly'); //急速问答创建订单

$router->get('/againPay', 'OrderController@againPay'); // 再次支付 againPay

// d订单申请退款
$router->get('/refundOrder', 'OrderController@refundOrder'); // 再次支付 againPay
// 彬享计划createBingxiang
$router->get('/bingxiang', 'OrderController@createBingxiang'); 




// 知学府
$router->get('/zhixue_onshow', 'ZhixueController@onshow');  //知学府初始化
$router->get('/zhixue_index', 'ZhixueController@index');  //知学府列表
$router->get('/zhixue_link', 'ZhixueController@link');  //知学府州、区、学校，联动


// 导师
$router->get('/tutor_info', 'Controller@getInfo');  //获取导师信息




$router->get('/celebrity_teacher_ask', 'CelebrityController@serverAsk');  //学鸽问询
// 
 $router->get('/celebrity_teacher_askprice', 'CelebrityController@serverAskPrice');  //[问询] 下面的数据价格

 $router->get('/jinwen_index', 'JinwenController@index');  //锦文殿列表初始化  
 $router->get('/jinwen_list', 'JinwenController@jinwenList');  //锦文殿列表分页
 $router->get('/jinwen_getprocess', 'JinwenController@getprocess');  //锦文殿确认订单初始化
 $router->get('/playmajor_index', 'PlaymajorController@index');  //（初识留学,申请院校,申请专业 ）初始化
 $router->get('/playmajor_tutorList', 'PlaymajorController@tutorList');  //（初识留学,申请院校,申请专业 ）初始化
 $router->get('/manage_index', 'ManageController@index');  //学鸽监理列表
 $router->get('/manage_initialize_index', 'ManageController@initialize_index');  //学鸽监理初始化

 $router->get('/manage_serve_content', 'ManageController@getServeContent');  //获取服务内容(价格)  申请院校，初识留学，申请专业，学鸽监理




 $router->get('/manage_serve_content', 'ManageController@getServeContent'); 
 
 $router->get('/mypraise_inc_piraise', 'MypraiseController@addPraise'); //点赞
 $router->get('/mypraise_dec_piraise', 'MypraiseController@removePraise'); //取消点赞
 $router->get('/mypraise_praiseorbrowse', 'MypraiseController@getPraiseorBrowse'); //我赞过

 $router->get('/index_information', 'IndexController@information'); //留学情报局
 $router->get('/information_Article', 'IndexController@tutorArticle'); //3.0 留学情报局-文章
 $router->get('/information_demand', 'IndexController@demand'); //留学情报局-视频（点播课）
 $router->get('/information_leader', 'IndexController@leaderAndsister'); //留学情报局-往期回放
 $router->get('/course_details', 'LiveController@course_details'); //直播/点播详情
 $router->get('/article_article_detail', 'ArticleController@article_detail'); //文章详情
 
 // 海外鸽服和留学指导广告图
$router->get('/index_adv', 'IndexController@getAdv');


$router->get('/follow_addInc', 'FollowController@addInc'); //转发增加



$router->get('/applay_tutor', 'TutorController@applay_tutor'); //申请成为导师



$router->get('/order_reply_push', 'ShareController@order_reply_push'); //3.0 订单回复推送

$router->get('/addBroadcast', 'BroadcastController@addBroadcast'); //直播用户添加








 





 // 

 







