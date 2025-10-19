<?php
namespace app\middleware;

use app\model\User as UserModel;

class AuthToken
{
    public function handle($request, \Closure $next)
    {
        $token = $request->header('Authorization', $request->param('token', ''));

        if ($token) {
            $user = UserModel::where('token', $token)->find();
            if ($user) {
                $request->user = $user;
            }
        }

        return $next($request);
    }
}
