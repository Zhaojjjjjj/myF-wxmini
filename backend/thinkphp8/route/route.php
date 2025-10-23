<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

use think\facade\Route;

// 用户相关路由
Route::get('user/info', 'user/info');
Route::post('user/update', 'user/update');
Route::post('user/login', 'user/login');  // Added login route
// 临时移除中间件用于调试
Route::post('user/avatar', 'user/uploadAvatar');

// 房间相关路由 (需要认证)
Route::post('room/create', 'room/create')->middleware(\app\middleware\AuthToken::class);
Route::post('room/join', 'room/join')->middleware(\app\middleware\AuthToken::class);
Route::get('room/detail', 'room/detail')->middleware(\app\middleware\AuthToken::class);
Route::post('room/exit', 'room/exit')->middleware(\app\middleware\AuthToken::class);
Route::get('room/qrcode', 'room/getQrCode');
Route::post('transfer', 'transfer/transfer')->middleware(\app\middleware\AuthToken::class);
