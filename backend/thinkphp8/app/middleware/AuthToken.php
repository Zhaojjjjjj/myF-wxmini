<?php
namespace app\middleware;

use app\model\User as UserModel;
use think\facade\Log;

class AuthToken
{
    public function handle($request, \Closure $next)
    {
        // 从请求头或参数中获取 token
        $token = $request->header('Authorization', '');
        
        // 如果请求头中没有，尝试从参数中获取
        if (empty($token)) {
            $token = $request->param('token', '');
        }

        Log::info('AuthToken中间件 - URL: ' . $request->url());
        Log::info('AuthToken中间件 - Token: ' . ($token ? substr($token, 0, 20) . '...' : 'empty'));

        if (empty($token)) {
            Log::warning('AuthToken中间件 - 未提供token');
            return json([
                'code' => 401,
                'msg' => '未提供认证令牌'
            ]);
        }

        $user = UserModel::where('token', $token)->find();
        
        if (!$user) {
            Log::warning('AuthToken中间件 - token无效: ' . substr($token, 0, 20) . '...');
            return json([
                'code' => 401,
                'msg' => '认证令牌无效或已过期'
            ]);
        }
        
        Log::info('AuthToken中间件 - 用户认证成功: ' . $user->id);
        $request->user = $user;

        return $next($request);
    }
}
