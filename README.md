# 多人实时记分小程序

## 项目结构

```
.
├── backend/thinkphp8          # 后端代码 (ThinkPHP8)
│   ├── app                    # 应用目录
│   │   ├── controller         # 控制器
│   │   └── model              # 模型
│   ├── route                  # 路由配置
│   └── vendor                 # Composer依赖
├── frontend                   # 前端代码 (微信小程序)
│   ├── pages                  # 页面目录
│   │   ├── index              # 首页
│   │   └── room               # 房间页
│   └── app.js/app.json/...    # 小程序配置文件
├── websocket                  # WebSocket服务端
│   └── server.php             # WebSocket服务端实现
└── database                   # 数据库相关
    └── init.sql               # 数据库初始化脚本
```

## 功能模块说明

### 后端 (ThinkPHP8)

1. **用户模块** (`app/controller/User.php`)
   - 获取用户信息
   - 更新用户信息（昵称、头像）

2. **房间模块** (`app/controller/Room.php`)
   - 创建房间
   - 加入房间

3. **转账模块** (`app/controller/Transfer.php`)
   - 处理用户间的转账请求

4. **模型层**
   - `app/model/User.php` - 用户模型
   - `app/model/Room.php` - 房间模型
   - `app/model/RoomMember.php` - 房间成员模型
   - `app/model/TransferLog.php` - 转账日志模型

### 前端 (微信小程序)

1. **首页** (`pages/index`)
   - 显示"创建房间"按钮或"退出房间"按钮
   - 自动跳转到房间页面

2. **房间页** (`pages/room`)
   - 显示房间成员列表
   - 显示转账日志
   - 提供刷新、邀请、转账等功能

### WebSocket 服务端

- 实现了基本的WebSocket通信逻辑
- 处理房间内的实时消息广播
- 处理用户加入、退出、转账等事件

### 数据库

- 包含用户、房间、房间成员、转账日志四个表
- 支持房间状态管理和转账记录追踪