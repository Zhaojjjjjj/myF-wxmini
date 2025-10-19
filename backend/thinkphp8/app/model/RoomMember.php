<?php
namespace app\model;

use think\Model;

class RoomMember extends Model
{
    // 设置表名
    protected $table = 'room_members';

    // 设置主键
    protected $pk = 'id';

    // 设置字段信息
    protected $schema = [
        'id' => 'int',
        'room_id' => 'int',
        'user_id' => 'int',
        'joined_at' => 'datetime'
    ];

    // 关联用户
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}