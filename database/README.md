# 数据库导入说明

## 导入步骤

1. 确保MySQL服务已启动

2. 登录MySQL
   ```bash
   mysql -u root -p
   ```

3. 创建数据库
   ```sql
   CREATE DATABASE score_tracker DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

4. 退出MySQL
   ```sql
   exit;
   ```

5. 导入SQL文件
   ```bash
   mysql -u root -p score_tracker < init.sql
   ```

## 数据库配置

在后端项目中，需要修改 `backend/thinkphp8/.env` 文件中的数据库配置：

```env
[DATABASE]
TYPE = mysql
HOSTNAME = 127.0.0.1
DATABASE = score_tracker
USERNAME = root
PASSWORD = root
HOSTPORT = 3306
CHARSET = utf8mb4
DEBUG = true
```

请根据实际的数据库配置修改相应的参数。