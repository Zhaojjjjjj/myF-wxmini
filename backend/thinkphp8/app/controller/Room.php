<?php
namespace app\controller;

use think\Request;
use think\Response;
use app\model\Room as RoomModel;
use app\model\User as UserModel;
use app\model\RoomMember as RoomMemberModel;
use app\model\TransferLog as TransferLogModel;

class Room
{
    // 创建房间
    public function create(Request $request)
    {
        $user = $request->user; // 假设已经通过中间件获取用户信息
        if (!$user) {
            return json(['code' => 401, 'msg' => '未登录']);
        }

        // 先退出当前房间（如果有）
        if ($user->current_room_id) {
            // 删除旧房间成员记录
            RoomMemberModel::where('user_id', $user->id)->delete();
            // 清空当前房间ID
            $user->current_room_id = null;
            $user->save();
        }

        // 创建房间
        $room = new RoomModel();
        $room->room_code = uniqid(); // 简单的唯一标识，实际可使用UUID
        $room->status = 'active';
        $room->save();

        // 用户加入房间
        $roomMember = new RoomMemberModel();
        $roomMember->room_id = $room->id;
        $roomMember->user_id = $user->id;
        $roomMember->joined_at = date('Y-m-d H:i:s');
        $roomMember->save();

        // 更新用户当前房间
        $user->current_room_id = $room->id;
        $user->score = 0; // 重置分数
        $user->save();

        return json([
            'code' => 200,
            'msg' => '创建成功',
            'data' => [
                'room_id' => $room->id,
                'room_code' => $room->room_code
            ]
        ]);
    }

    // 加入房间
    public function join(Request $request)
    {
        $user = $request->user; // 假设已经通过中间件获取用户信息
        if (!$user) {
            return json(['code' => 401, 'msg' => '未登录']);
        }

        $roomId = $request->param('room_id');
        $room = RoomModel::where('id', $roomId)->where('status', 'active')->find();
        if (!$room) {
            return json(['code' => 404, 'msg' => '房间不存在或已关闭']);
        }

        // 检查房间人数是否已满（假设上限为20）
        $memberCount = RoomMemberModel::where('room_id', $roomId)->count();
        if ($memberCount >= 20) {
            return json(['code' => 400, 'msg' => '房间人数已满']);
        }

        // 检查用户是否已在该房间中
        $existingMember = RoomMemberModel::where('room_id', $roomId)->where('user_id', $user->id)->find();
        if ($existingMember) {
            // 已在该房间中，直接返回成功（允许重复加入，避免前后端状态不一致）
            return json([
                'code' => 200,
                'msg' => '加入成功',
                'data' => [
                    'room_id' => $room->id,
                    'room_code' => $room->room_code
                ]
            ]);
        }

        // 先退出当前房间（如果在其他房间）
        if ($user->current_room_id && $user->current_room_id != $roomId) {
            // 删除旧房间成员记录
            RoomMemberModel::where('user_id', $user->id)->delete();
        }

        // 用户加入房间
        $roomMember = new RoomMemberModel();
        $roomMember->room_id = $room->id;
        $roomMember->user_id = $user->id;
        $roomMember->joined_at = date('Y-m-d H:i:s');
        $roomMember->save();

        // 更新用户当前房间
        $user->current_room_id = $room->id;
        $user->score = 0; // 重置分数
        $user->save();

        return json([
            'code' => 200,
            'msg' => '加入成功',
            'data' => [
                'room_id' => $room->id,
                'room_code' => $room->room_code
            ]
        ]);
    }

    // 获取房间详情
    public function detail(Request $request)
    {
        $user = $request->user;
        if (!$user) {
            return json(['code' => 401, 'msg' => '未登录']);
        }

        $roomId = $request->param('room_id');
        $room = RoomModel::where('id', $roomId)->where('status', 'active')->find();
        if (!$room) {
            return json(['code' => 404, 'msg' => '房间不存在或已关闭']);
        }

        // 获取房间成员
        $members = RoomMemberModel::where('room_id', $roomId)
            ->with('user') // 关联用户信息
            ->select();

        $memberList = [];
        foreach ($members as $member) {
            $memberList[] = [
                'id' => $member->user->id,
                'nickname' => $member->user->nickname,
                'avatar_url' => $member->user->avatar_url,
                'score' => $member->user->score
            ];
        }

        // 获取转账记录
        $logs = TransferLogModel::where('room_id', $roomId)
            ->order('created_at', 'desc')
            ->limit(20) // 只获取最近20条记录
            ->select();

        $logList = [];
        foreach ($logs as $log) {
            // 获取用户信息
            $fromUser = UserModel::where('id', $log->from_user_id)->find();
            $toUser = UserModel::where('id', $log->to_user_id)->find();
            
            $logList[] = [
                'id' => $log->id,
                'from_user_nickname' => $fromUser ? $fromUser->nickname : '未知用户',
                'to_user_nickname' => $toUser ? $toUser->nickname : '未知用户',
                'amount' => $log->amount,
                'created_at' => $log->created_at
            ];
        }

        return json([
            'code' => 200,
            'msg' => '获取成功',
            'data' => [
                'room' => [
                    'id' => $room->id,
                    'room_code' => $room->room_code
                ],
                'members' => $memberList,
                'logs' => $logList
            ]
        ]);
    }

    // 退出房间
    public function exit(Request $request)
    {
        $user = $request->user;
        if (!$user) {
            return json(['code' => 401, 'msg' => '未登录']);
        }

        $roomId = $request->param('room_id');
        $room = RoomModel::where('id', $roomId)->where('status', 'active')->find();
        if (!$room) {
            return json(['code' => 404, 'msg' => '房间不存在或已关闭']);
        }

        // 删除房间成员记录
        $roomMember = RoomMemberModel::where('room_id', $roomId)->where('user_id', $user->id)->find();
        if ($roomMember) {
            $roomMember->delete();
        }

        // 更新用户当前房间
        $user->current_room_id = null;
        $user->save();

        return json([
            'code' => 200,
            'msg' => '退出成功'
        ]);
    }

    // 生成房间小程序码
    public function getQrCode(Request $request)
    {
        \think\facade\Log::info('小程序码请求 - 开始处理');
        
        // 支持从查询参数或header获取token进行验证
        $token = $request->param('token') ?: $request->header('Authorization');
        
        \think\facade\Log::info('小程序码请求 - Token: ' . ($token ? substr($token, 0, 20) . '...' : 'empty'));
        
        if (!$token) {
            \think\facade\Log::warning('小程序码请求 - 未提供token');
            return json(['code' => 401, 'msg' => '未登录']);
        }
        
        // 验证token
        $user = UserModel::where('token', $token)->find();
        if (!$user) {
            \think\facade\Log::warning('小程序码请求 - token无效');
            return json(['code' => 401, 'msg' => '登录信息无效']);
        }
        
        $roomId = $request->param('room_id');
        
        \think\facade\Log::info('小程序码请求 - 房间ID: ' . $roomId);
        
        if (!$roomId) {
            return json(['code' => 400, 'msg' => '房间ID参数缺失']);
        }

        $room = RoomModel::where('id', $roomId)->where('status', 'active')->find();
        if (!$room) {
            \think\facade\Log::warning('小程序码请求 - 房间不存在: ' . $roomId);
            return json(['code' => 404, 'msg' => '房间不存在或已关闭']);
        }

        // 检查缓存的小程序码
        $runtimePath = app()->getRuntimePath();
        $qrcodeDir = $runtimePath . 'qrcode';
        $qrcodePath = $qrcodeDir . DIRECTORY_SEPARATOR . 'room_' . $roomId . '.png';
        
        \think\facade\Log::info('小程序码请求 - 缓存路径: ' . $qrcodePath);
        
        if (file_exists($qrcodePath) && (time() - filemtime($qrcodePath)) < 86400) {
            // 24小时内的缓存直接返回
            \think\facade\Log::info('小程序码请求 - 使用缓存');
            $qrCodeImage = file_get_contents($qrcodePath);
            return response($qrCodeImage, 200, [
                'Content-Type' => 'image/png',
                'Cache-Control' => 'public, max-age=86400'
            ]);
        }

        \think\facade\Log::info('小程序码请求 - 生成新的小程序码');

        // 获取微信配置
        $config = config('wechat.mini_program');
        $appId = $config['app_id'];
        $secret = $config['secret'];
        
        \think\facade\Log::info('小程序码请求 - AppID: ' . $appId);

        // 获取access_token
        $tokenUrl = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appId}&secret={$secret}";
        
        try {
            // 使用curl代替file_get_contents，更稳定
            $ch = curl_init($tokenUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            $tokenResponse = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200 || !$tokenResponse) {
                throw new \Exception('获取access_token网络请求失败');
            }
            
            $tokenData = json_decode($tokenResponse, true);
            
            if (isset($tokenData['errcode']) && $tokenData['errcode'] != 0) {
                throw new \Exception('获取access_token失败: ' . ($tokenData['errmsg'] ?? 'Unknown error'));
            }
            
            if (!isset($tokenData['access_token'])) {
                throw new \Exception('access_token不存在');
            }
            
            $accessToken = $tokenData['access_token'];
            
            // 生成小程序码
            $qrCodeUrl = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token={$accessToken}";
            
            // 根据请求来源判断环境版本
            // 体验版和正式版都使用release，开发环境使用develop
            $envVersion = 'release'; // 默认使用正式版
            
            // 如果是本地开发环境，使用develop
            if (app()->isDebug()) {
                $envVersion = 'develop';
            }
            
            \think\facade\Log::info('小程序码请求 - 环境版本: ' . $envVersion);
            
            $params = [
                'scene' => (string)$roomId,
                'page' => 'pages/room/room',
                'width' => 430,
                'check_path' => false,
                'env_version' => $envVersion
            ];
            
            \think\facade\Log::info('小程序码请求 - 请求参数', $params);
            
            // 发送POST请求生成小程序码
            $ch = curl_init($qrCodeUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            $qrCodeImage = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            \think\facade\Log::info('小程序码请求 - 响应状态', [
                'http_code' => $httpCode,
                'response_size' => strlen($qrCodeImage),
                'curl_error' => $curlError
            ]);
            
            if ($httpCode !== 200 || !$qrCodeImage) {
                throw new \Exception('生成小程序码网络请求失败: HTTP ' . $httpCode . ($curlError ? ', ' . $curlError : ''));
            }
            
            // 检查返回的是否是图片
            $jsonCheck = json_decode($qrCodeImage, true);
            if ($jsonCheck !== null && isset($jsonCheck['errcode'])) {
                \think\facade\Log::error('小程序码生成 - 微信API错误', $jsonCheck);
                throw new \Exception('微信API错误(' . $jsonCheck['errcode'] . '): ' . ($jsonCheck['errmsg'] ?? 'Unknown error'));
            }
            
            // 确保目录存在
            if (!is_dir($qrcodeDir)) {
                \think\facade\Log::info('小程序码生成 - 创建缓存目录: ' . $qrcodeDir);
                if (!@mkdir($qrcodeDir, 0755, true)) {
                    $error = error_get_last();
                    \think\facade\Log::error('小程序码生成 - 创建目录失败', [
                        'path' => $qrcodeDir,
                        'error' => $error['message'] ?? 'unknown'
                    ]);
                    // 目录创建失败不影响返回，只是不缓存
                }
            }
            
            // 检查目录是否可写
            if (!is_writable($qrcodeDir)) {
                @chmod($qrcodeDir, 0755);
            }
            
            // 保存到本地缓存（如果目录可写）
            if (is_writable($qrcodeDir)) {
                if (@file_put_contents($qrcodePath, $qrCodeImage)) {
                    \think\facade\Log::info('小程序码生成 - 缓存保存成功: ' . $qrcodePath);
                } else {
                    \think\facade\Log::warning('小程序码生成 - 缓存保存失败: ' . $qrcodePath);
                }
            } else {
                \think\facade\Log::warning('小程序码生成 - 缓存目录不可写，跳过缓存: ' . $qrcodeDir);
            }
            
            // 直接返回图片
            return response($qrCodeImage, 200, [
                'Content-Type' => 'image/png',
                'Cache-Control' => 'public, max-age=86400'
            ]);
            
        } catch (\Exception $e) {
            \think\facade\Log::error('生成小程序码失败', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'room_id' => $roomId,
                'trace' => $e->getTraceAsString()
            ]);
            
            // 返回详细错误信息，便于调试
            return json([
                'code' => 500, 
                'msg' => '生成小程序码失败: ' . $e->getMessage(),
                'data' => [
                    'error_detail' => [
                        'message' => $e->getMessage(),
                        'file' => basename($e->getFile()),
                        'line' => $e->getLine()
                    ]
                ]
            ]);
        }
    }
}
