<?php
// 简单的WebSocket服务端实现示例（基于Ratchet库）
// 需要先安装Ratchet：composer require ratchet/pawl ratchet/rfc6455

require dirname(__DIR__) . '/backend/thinkphp8/vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\WebSocket\WsServer;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;

class WebSocketServer implements MessageComponentInterface
{
    protected $clients;
    protected $rooms; // 以房间ID为键，存储该房间的所有连接

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->rooms = [];
    }

    public function onOpen(ConnectionInterface $conn)
    {
        // 从查询参数中获取房间ID
        $queryParams = [];
        parse_str($conn->httpRequest->getUri()->getQuery(), $queryParams);
        $roomId = $queryParams['room_id'] ?? null;

        if (!$roomId) {
            $conn->send(json_encode(['type' => 'error', 'msg' => '缺少房间ID']));
            $conn->close();
            return;
        }

        // 将连接与房间关联
        $conn->roomId = $roomId;
        $this->clients->attach($conn);

        if (!isset($this->rooms[$roomId])) {
            $this->rooms[$roomId] = new \SplObjectStorage;
        }
        $this->rooms[$roomId]->attach($conn);

        echo "新连接 ({$conn->resourceId}) 加入房间 {$roomId}\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg, true);
        if (!$data) {
            $from->send(json_encode(['type' => 'error', 'msg' => '无效的数据格式']));
            return;
        }

        switch ($data['type']) {
            case 'join_room':
                // 加入房间逻辑已在onOpen中处理
                break;
            case 'transfer':
                $this->handleTransfer($from, $data);
                break;
            case 'edit_profile':
                $this->handleEditProfile($from, $data);
                break;
            case 'user_exit':
                $this->handleUserExit($from);
                break;
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        if (isset($conn->roomId) && isset($this->rooms[$conn->roomId])) {
            $this->rooms[$conn->roomId]->detach($conn);
            echo "连接 ({$conn->resourceId}) 离开房间 {$conn->roomId}\n";
            
            // 如果房间为空，可以考虑启动销毁计时器
            if ($this->rooms[$conn->roomId]->count() === 0) {
                echo "房间 {$conn->roomId} 无人，启动销毁计时器\n";
                // 这里可以实现销毁房间的逻辑
            }
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "发生错误: {$e->getMessage()}\n";
        $conn->close();
    }

    // 处理转账请求
    private function handleTransfer($from, $data)
    {
        // 这里应该调用ThinkPHP的逻辑来处理转账
        // 为简化示例，直接广播转账信息
        $roomId = $from->roomId;
        $transferData = [
            'type' => 'log_update',
            'log' => [
                'id' => time(), // 简单的ID生成
                'from_user_nickname' => $data['from_user_nickname'],
                'to_user_nickname' => $data['to_user_nickname'],
                'amount' => $data['amount'],
                'created_at' => date('Y-m-d H:i:s')
            ]
        ];
        
        // 广播给房间内所有成员
        if (isset($this->rooms[$roomId])) {
            foreach ($this->rooms[$roomId] as $client) {
                $client->send(json_encode($transferData));
            }
        }
    }

    // 处理修改资料请求
    private function handleEditProfile($from, $data)
    {
        // 这里应该调用ThinkPHP的逻辑来处理资料修改
        // 为简化示例，直接广播修改信息
        $roomId = $from->roomId;
        $profileData = [
            'type' => 'room_update',
            'members' => $data['members'] // 假设客户端已更新并发送完整的成员列表
        ];
        
        // 广播给房间内所有成员
        if (isset($this->rooms[$roomId])) {
            foreach ($this->rooms[$roomId] as $client) {
                $client->send(json_encode($profileData));
            }
        }
    }

    // 处理用户退出请求
    private function handleUserExit($from)
    {
        $roomId = $from->roomId;
        
        // 通知房间内其他成员有用户退出
        $exitData = [
            'type' => 'room_update',
            'members' => [] // 这里应该包含更新后的成员列表
        ];
        
        // 广播给房间内所有成员
        if (isset($this->rooms[$roomId])) {
            foreach ($this->rooms[$roomId] as $client) {
                if ($client !== $from) {
                    $client->send(json_encode($exitData));
                }
            }
        }
        
        // 关闭连接
        $from->close();
    }
}

// 启动WebSocket服务器
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new WebSocketServer()
        )
    ),
    8080
);

echo "WebSocket服务器启动在端口 8080\n";
$server->run();