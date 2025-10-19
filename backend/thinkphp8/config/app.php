<?php
// +----------------------------------------------------------------------
// | 应用设置
// +----------------------------------------------------------------------

return [
    // 应用地址
    'app_host'         => env('app.host', ''),
    // 应用的命名空间
    'app_namespace'    => '',
    // 是否启用路由
    'with_route'       => true,
    // 是否启用事件
    'with_event'       => true,
    // 自动多应用模式
    'auto_multi_app'   => false,
    // 应用映射
    'app_map'          => [],
    // 域名绑定
    'domain_bind'      => [],
    // 禁止URL访问的应用列表
    'deny_app_list'    => [],
    // 默认应用
    'default_app'      => 'index',
    // 默认时区
    'default_timezone' => 'Asia/Shanghai',
    // 应用类库后缀
    'class_suffix'     => false,
    // 控制器类后缀
    'controller_suffix' => false,
];