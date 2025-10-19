<?php
namespace app\model;

use think\Model;

class User extends Model
{
    // 设置表名
    protected $table = 'users';

    // 设置主键
    protected $pk = 'id';

    // 设置字段信息
    protected $schema = [
        'id' => 'int',
        'openid' => 'string',
        'nickname' => 'string',
        'avatar_url' => 'string',
        'current_room_id' => 'int',
        'score' => 'int',
        'token' => 'string',
        'session_key' => 'string',
        'created_at' => 'datetime'
    ];
}