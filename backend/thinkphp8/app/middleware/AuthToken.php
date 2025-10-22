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
        
        // 记录调试信息
        Log::info('AuthToken middleware - Token: ' . ($token ? substr($token, 0, 10) . '...' : 'empty'));
        Log::info('AuthToken middleware - Headers: ' . json_encode($request->header()));

        if (empty($token)) {
            return json([
                'code' => 401,
                'msg' => '未提供认证令牌'
            ]);
        }

        $user = UserModel::where('token', $token)->find();
        
        if (!$user) {
            Log::warning('AuthToken middleware - Invalid token: ' . substr($token, 0, 10) . '...');
            return json([
                'code' => 401,
                'msg' => '认证令牌无效或已过期'
            ]);
        }
        
        Log::info('AuthToken middleware - User authenticated: ' . $user->id);
        $request->user = $user;

        return $next($request);
    }
}
