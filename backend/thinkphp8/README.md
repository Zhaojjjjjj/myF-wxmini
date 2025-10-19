# 后端项目部署说明

## 宝塔面板部署步骤

### 1. 环境要求
- PHP >= 7.2.5
- MySQL >= 5.7
- Composer
- 宝塔面板

### 2. 部署步骤

#### 2.1. 上传代码
1. 将项目代码上传到宝塔面板的网站目录中
2. 或者使用Git克隆项目到网站目录

#### 2.2. 安装依赖
1. 进入项目根目录：
   ```bash
   cd /www/wwwroot/your-domain.com
   ```
2. 安装Composer依赖：
   ```bash
   composer install
   ```

#### 2.3. 配置数据库
1. 在宝塔面板中创建数据库
2. 导入`database/init.sql`文件
3. 修改`.env`文件中的数据库配置：
   ```env
   [DATABASE]
   TYPE = mysql
   HOSTNAME = 127.0.0.1
   DATABASE = your_database_name
   USERNAME = your_database_username
   PASSWORD = your_database_password
   HOSTPORT = 3306
   CHARSET = utf8mb4
   ```

#### 2.4. 配置网站
1. 在宝塔面板中添加网站
2. 设置网站目录为项目根目录
3. 设置运行目录为`public`
4. 设置伪静态规则（ThinkPHP规则）：
   ```nginx
   location / {
       if (!-e $request_filename){
           rewrite  ^(.*)$  /index.php?s=$1  last;   break;
       }
   }
   ```

#### 2.5. 启动WebSocket服务
1. 安装WebSocket依赖：
   ```bash
   composer require ratchet/rfc6455 ratchet/pawl
   ```
2. 启动WebSocket服务：
   ```bash
   php websocket.php
   ```
3. 为了保持WebSocket服务在后台运行，可以使用以下方法之一：
   - 使用nohup：
     ```bash
     nohup php websocket.php > websocket.log 2>&1 &
     ```
   - 使用supervisor（推荐）：
     ```ini
     [program:websocket]
     command=php /www/wwwroot/your-domain.com/websocket.php
     directory=/www/wwwroot/your-domain.com
     user=www
     autostart=true
     autorestart=true
     stdout_logfile=/var/log/websocket.log
     stderr_logfile=/var/log/websocket_error.log
     ```

### 3. 注意事项
1. 确保服务器防火墙开放了8080端口（WebSocket服务端口）
2. 如果使用HTTPS，需要配置WebSocket服务支持WSS
3. 定期检查WebSocket服务运行状态