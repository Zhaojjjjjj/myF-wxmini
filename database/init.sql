
-- 创建用户表
CREATE TABLE `users` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '用户ID',
  `openid` VARCHAR(64) NOT NULL COMMENT '微信用户唯一标识',
  `nickname` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '昵称',
  `avatar_url` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '头像URL',
  `current_room_id` INT NULL DEFAULT NULL COMMENT '当前所在房间ID',
  `score` INT NOT NULL DEFAULT 0 COMMENT '当前分数',
  `token` VARCHAR(64) DEFAULT NULL COMMENT '登录令牌',
  `session_key` VARCHAR(128) DEFAULT NULL COMMENT '微信会话密钥',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_openid` (`openid`),
  KEY `idx_current_room_id` (`current_room_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户表';

-- 创建房间表
CREATE TABLE `rooms` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '房间ID',
  `room_code` VARCHAR(64) NOT NULL COMMENT '唯一房间标识',
  `status` ENUM('active', 'closed') NOT NULL DEFAULT 'active' COMMENT '房间状态',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_room_code` (`room_code`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='房间表';

-- 创建房间成员表
CREATE TABLE `room_members` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '主键',
  `room_id` INT NOT NULL COMMENT '房间ID',
  `user_id` INT NOT NULL COMMENT '用户ID',
  `joined_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '加入时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_room_user` (`room_id`, `user_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='房间成员表';

-- 创建转账日志表
CREATE TABLE `transfer_logs` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '主键',
  `room_id` INT NOT NULL COMMENT '房间ID',
  `from_user_id` INT NOT NULL COMMENT '转出用户ID',
  `to_user_id` INT NOT NULL COMMENT '转入用户ID',
  `amount` INT NOT NULL COMMENT '金额',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_room_id` (`room_id`),
  KEY `idx_from_user_id` (`from_user_id`),
  KEY `idx_to_user_id` (`to_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='转账日志表';
