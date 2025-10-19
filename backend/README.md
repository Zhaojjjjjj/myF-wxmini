# 后端项目启动说明

## 环境要求
- PHP >= 7.2.5
- MySQL >= 5.7
- Composer

## 安装步骤

1. 进入项目目录
   ```bash
   cd backend/thinkphp8
   ```

2. 安装依赖
   ```bash
   composer install
   ```

3. 配置数据库
   - 修改 `.env` 文件中的数据库配置信息
   - 确保数据库用户名和密码正确

4. 导入数据库
   - 使用 `database/init.sql` 文件创建数据库和表结构

5. 启动项目
   ```bash
   php think run
   ```
   或者使用内置服务器：
   ```bash
   cd public
   php -S localhost:8000
   ```

6. 启动WebSocket服务
   ```bash
   php ../websocket/server.php
   ```

## 目录结构
```
thinkphp8/
├── app/           # 应用目录
├── config/        # 配置目录
├── public/        # 公共目录（入口文件）
├── route/         # 路由定义
└── vendor/        # Composer依赖
```