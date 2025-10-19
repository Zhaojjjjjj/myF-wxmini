<?php
namespace app\model;

use think\Model;

class Room extends Model
{
    // 设置表名
    protected $table = 'rooms';

    // 设置主键
    protected $pk = 'id';

    // 设置字段信息
    protected $schema = [
        'id' => 'int',
        'room_code' => 'string',
        'status' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}