<?php
namespace app\model;

use think\Model;

class TransferLog extends Model
{
    // 设置表名
    protected $table = 'transfer_logs';

    // 设置主键
    protected $pk = 'id';

    // 设置字段信息
    protected $schema = [
        'id' => 'int',
        'room_id' => 'int',
        'from_user_id' => 'int',
        'to_user_id' => 'int',
        'amount' => 'int',
        'created_at' => 'datetime'
    ];
}