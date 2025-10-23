<?php
namespace app\controller;

use think\Request;
use think\facade\Config;
use think\facade\Log;
use think\facade\Cache;
use app\model\User as UserModel;

class User
{
    protected function getEnvConfig()
    {
        $config = Config::get('wechat.mini_program', []);

        return [
            'app_id' => $config['app_id'] ?? env('WECHAT_APPID', ''),
            'secret' => $config['secret'] ?? env('WECHAT_SECRET', ''),
        ];
    }

    protected function getTokenUser($token)
    {
        if (empty($token)) {
            return null;
        }

        return UserModel::where('token', $token)->find();
    }

    protected function normalizeUserResponse(UserModel $user)
    {
        $defaultAvatar = Config::get('wechat.mini_program.default_avatar');

        return [
            'id' => $user->id,
            'openid' => $user->openid,
            'nickname' => $user->nickname,
            'avatar_url' => $user->avatar_url ?: $defaultAvatar,
            'current_room_id' => $user->current_room_id,
            'score' => $user->score,
            'created_at' => $user->created_at,
            'token' => $user->token,
        ];
    }

    public function login(Request $request)
    {
        $code = $request->param('code');

        if (empty($code)) {
            return json([
                'code' => 400,
                'msg' => '缺少code参数',
                'data' => null
            ]);
        }

        $config = $this->getEnvConfig();

        if (empty($config['app_id']) || empty($config['secret'])) {
            Log::error('微信小程序配置缺失: appid or secret');
            return json([
                'code' => 500,
                'msg' => '服务器配置错误',
                'data' => null
            ]);
        }

        $sessionUrl = sprintf(
            'https://api.weixin.qq.com/sns/jscode2session?appid=%s&secret=%s&js_code=%s&grant_type=authorization_code',
            $config['app_id'],
            $config['secret'],
            $code
        );

        try {
            // 使用 curl 替代 file_get_contents，更稳定
            $ch = curl_init($sessionUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($response === false || $httpCode !== 200) {
                throw new \Exception('请求微信接口失败: ' . $curlError);
            }
            
            $sessionData = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('解析微信响应失败');
            }
        } catch (\Throwable $e) {
            Log::error('微信登录接口调用失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'msg' => '登录失败，请稍后再试',
                'data' => null
            ]);
        }

        if (isset($sessionData['errcode']) && $sessionData['errcode'] != 0) {
            Log::warning('微信登录失败: ' . json_encode($sessionData, JSON_UNESCAPED_UNICODE));
            return json([
                'code' => 500,
                'msg' => '微信登录失败: ' . ($sessionData['errmsg'] ?? '未知错误'),
                'data' => null
            ]);
        }

        $openid = $sessionData['openid'] ?? null;
        $sessionKey = $sessionData['session_key'] ?? null;

        if (!$openid || !$sessionKey) {
            Log::warning('微信登录返回数据缺失: ' . json_encode($sessionData, JSON_UNESCAPED_UNICODE));
            return json([
                'code' => 500,
                'msg' => '微信登录信息不完整',
                'data' => null
            ]);
        }

        $user = UserModel::where('openid', $openid)->find();

        $defaultAvatar = Config::get('wechat.mini_program.default_avatar');

        if (!$user) {
            $user = new UserModel();
            $user->openid = $openid;
            $user->nickname = '微信用户';
            $user->avatar_url = $defaultAvatar;
            $user->current_room_id = null;
            $user->score = 0;
        } elseif (empty($user->avatar_url)) {
            $user->avatar_url = $defaultAvatar;
        }

        $user->session_key = $sessionKey;
        $user->token = bin2hex(random_bytes(16));
        $user->save();

        return json([
            'code' => 200,
            'msg' => '登录成功',
            'data' => $this->normalizeUserResponse($user)
        ]);
    }

    public function info(Request $request)
    {
        $token = $request->header('Authorization', $request->param('token', ''));

        if (empty($token)) {
            return json([
                'code' => 401,
                'msg' => '未提供验证信息',
                'data' => null
            ]);
        }

        $user = $this->getTokenUser($token);

        if (!$user) {
            return json([
                'code' => 401,
                'msg' => '用户未登录或不存在',
                'data' => null
            ]);
        }

        return json([
            'code' => 200,
            'msg' => '获取成功',
            'data' => $this->normalizeUserResponse($user)
        ]);
    }

    // 更新用户信息
    public function update(Request $request)
    {
        $token = $request->header('Authorization', $request->param('token', ''));

        $user = $this->getTokenUser($token);

        if (!$user) {
            return json([
                'code' => 401,
                'msg' => '未登录',
                'data' => null
            ]);
        }

        $nickname = $request->param('nickname', '');
        $avatarUrl = $request->param('avatar_url', '');

        if (!empty($nickname)) {
            $user->nickname = $nickname;
        }
        if (!empty($avatarUrl)) {
            $user->avatar_url = $avatarUrl;
        }
        $user->save();

        return json([
            'code' => 200,
            'msg' => '更新成功',
            'data' => $this->normalizeUserResponse($user)
        ]);
    }

    // 上传头像
    public function uploadAvatar(Request $request)
    {
        // 从中间件获取已验证的用户信息，如果没有则手动验证
        $user = $request->user ?? null;

        if (!$user) {
            // 手动验证token
            $token = $request->header('Authorization', '');
            if (empty($token)) {
                $token = $request->param('token', '');
            }
            
            if ($token) {
                $user = UserModel::where('token', $token)->find();
            }
            
            if (!$user) {
                return json([
                    'code' => 401,
                    'msg' => '未登录',
                    'data' => null
                ]);
            }
        }

        // 获取上传的文件
        $file = $request->file('avatar');

        if (!$file) {
            return json([
                'code' => 400,
                'msg' => '请选择要上传的头像文件',
                'data' => null
            ]);
        }

        // 验证文件是否有效
        if (!$file->isValid()) {
            return json([
                'code' => 400,
                'msg' => '文件上传失败，请重试',
                'data' => null
            ]);
        }

        // 验证文件类型
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = strtolower($file->getOriginalExtension());

        if (!in_array($extension, $allowedTypes)) {
            return json([
                'code' => 400,
                'msg' => '头像文件格式不支持，仅支持jpg、jpeg、png、gif、webp格式',
                'data' => null
            ]);
        }

        // 验证文件大小 (5MB)
        $maxSize = 5 * 1024 * 1024;
        if ($file->getSize() > $maxSize) {
            return json([
                'code' => 400,
                'msg' => '头像文件大小不能超过5MB',
                'data' => null
            ]);
        }

        try {
            // 生成唯一的文件名
            $fileName = 'avatar_' . $user->id . '_' . time() . '.' . $extension;

            // 移动文件到上传目录
            $savePath = app()->getRootPath() . 'public/uploads/avatars/';
            if (!is_dir($savePath)) {
                mkdir($savePath, 0755, true);
            }

            $file->move($savePath, $fileName);

            // 更新用户头像URL - 使用完整的URL
            $baseUrl = $request->domain();
            $avatarUrl = $baseUrl . '/uploads/avatars/' . $fileName;
            $user->avatar_url = $avatarUrl;
            $user->save();

            return json([
                'code' => 200,
                'msg' => '头像上传成功',
                'data' => [
                    'avatar_url' => $avatarUrl,
                    'user' => $this->normalizeUserResponse($user)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('头像上传失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'msg' => '头像上传失败',
                'data' => null
            ]);
        }
    }
}
