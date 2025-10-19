<?php
namespace app\controller;

use think\Request;
use think\Response;
use app\model\User as UserModel;
use app\model\Room as RoomModel;
use app\model\RoomMember as RoomMemberModel;
use app\model\TransferLog as TransferLogModel;

class Transfer
{
    // 转分
    public function transfer(Request $request)
    {
        $user = $request->user; // 假设已经通过中间件获取用户信息
        if (!$user) {
            return json(['code' => 401, 'msg' => '未登录']);
        }

        $toUserId = $request->param('to_user_id');
        $amount = $request->param('amount');

        // 验证参数
        if (!is_numeric($amount) || $amount <= 0 || $amount > 10000) {
            return json(['code' => 400, 'msg' => '转账金额必须在1-10000之间']);
        }

        // 检查用户是否在房间中
        if (!$user->current_room_id) {
            return json(['code' => 400, 'msg' => '您不在任何房间中']);
        }

        // 检查目标用户是否存在且在同一房间
        $toUser = UserModel::where('id', $toUserId)->where('current_room_id', $user->current_room_id)->find();
        if (!$toUser) {
            return json(['code' => 400, 'msg' => '目标用户不存在或不在同一房间']);
        }

        // 检查余额
        if ($user->score < $amount) {
            return json(['code' => 400, 'msg' => '余额不足']);
        }

        // 执行转账
        $user->score -= $amount;
        $toUser->score += $amount;
        $user->save();
        $toUser->save();

        // 记录日志
        $log = new TransferLogModel();
        $log->room_id = $user->current_room_id;
        $log->from_user_id = $user->id;
        $log->to_user_id = $toUser->id;
        $log->amount = $amount;
        $log->created_at = date('Y-m-d H:i:s');
        $log->save();

        return json([
            'code' => 200,
            'msg' => '转账成功',
            'data' => [
                'from_user_id' => $user->id,
                'to_user_id' => $toUser->id,
                'amount' => $amount
            ]
        ]);
    }
}