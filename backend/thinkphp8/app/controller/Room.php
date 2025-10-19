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

        // 检查用户是否已在房间中
        $existingMember = RoomMemberModel::where('room_id', $roomId)->where('user_id', $user->id)->find();
        if ($existingMember) {
            return json(['code' => 400, 'msg' => '您已在该房间中']);
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
        $user = $request->user;
        if (!$user) {
            return json(['code' => 401, 'msg' => '未登录']);
        }

        $roomId = $request->param('room_id');
        $room = RoomModel::where('id', $roomId)->where('status', 'active')->find();
        if (!$room) {
            return json(['code' => 404, 'msg' => '房间不存在或已关闭']);
        }

        // 获取微信配置
        $config = config('wechat.mini_program');
        $appId = $config['app_id'];
        $secret = $config['secret'];

        // 获取access_token
        $tokenUrl = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appId}&secret={$secret}";
        
        try {
            $tokenResponse = file_get_contents($tokenUrl);
            $tokenData = json_decode($tokenResponse, true);
            
            if (isset($tokenData['errcode']) && $tokenData['errcode'] != 0) {
                return json(['code' => 500, 'msg' => '获取access_token失败: ' . $tokenData['errmsg']]);
            }
            
            $accessToken = $tokenData['access_token'];
            
            // 生成小程序码
            $qrCodeUrl = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token={$accessToken}";
            
            // 构造参数
            $params = [
                'scene' => 'room_id=' . $roomId,
                'page' => 'pages/room/room',
                'width' => 430
            ];
            
            // 发送POST请求生成小程序码
            $options = [
                'http' => [
                    'header'  => "Content-type: application/json\r\n",
                    'method'  => 'POST',
                    'content' => json_encode($params)
                ]
            ];
            
            $context  = stream_context_create($options);
            $qrCodeImage = file_get_contents($qrCodeUrl, false, $context);
            
            if ($qrCodeImage === false) {
                return json(['code' => 500, 'msg' => '生成小程序码失败']);
            }
            
            // 直接返回图片
            return response($qrCodeImage, 200, ['Content-Type' => 'image/png']);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => '生成小程序码失败: ' . $e->getMessage()]);
        }
    }
}