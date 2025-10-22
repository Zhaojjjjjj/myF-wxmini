# 小程序码功能使用说明

## 功能概述

本项目实现了完整的小程序码生成和扫码进入房间功能，用户可以通过扫描小程序码直接加入房间。

## 后端实现

### 1. 配置微信小程序信息

编辑 `backend/thinkphp8/config/wechat.php`：

```php
return [
    'mini_program' => [
        'app_id'  => env('WECHAT_APPID', 'your_appid'),
        'secret'  => env('WECHAT_SECRET', 'your_secret'),
        'default_avatar' => env('WECHAT_DEFAULT_AVATAR', '默认头像URL'),
        'qrcode_page' => env('WECHAT_QRCODE_PAGE', 'pages/room/room'),
    ],
];
```

**推荐做法**：在项目根目录创建 `.env` 文件：

```env
WECHAT_APPID=wx5cb19c251351c841
WECHAT_SECRET=你的小程序secret
```

### 2. 小程序码生成接口

**接口地址**：`GET /room/qrcode`

**请求参数**：
- `room_id`：房间ID（必填）
- `token`：用户认证token（必填，可通过header或URL参数传递）

**返回内容**：PNG格式的小程序码图片（二进制流）

**功能特性**：
- ✅ 自动生成小程序码
- ✅ 本地缓存机制（24小时有效）
- ✅ 场景值传递房间ID
- ✅ 错误处理和日志记录
- ✅ 开发/生产环境区分

**技术实现**：
```php
// 使用微信官方API生成小程序码
$params = [
    'scene' => $roomId,  // 场景值
    'page' => 'pages/room/room',  // 小程序页面路径
    'width' => 430,
    'check_path' => false,
    'env_version' => 'develop'  // develop/trial/release
];
```

### 3. 缓存机制

- 小程序码生成后保存在 `runtime/qrcode/` 目录
- 文件名格式：`room_{room_id}.png`
- 缓存有效期：24小时
- 自动创建目录，权限755

## 前端实现

### 1. 显示小程序码

在房间页面点击"邀请"按钮弹窗显示小程序码：

```javascript
// 加载小程序码
loadQrCode() {
  const userInfo = wx.getStorageSync('user_info');
  const token = userInfo ? userInfo.token : '';
  const timestamp = new Date().getTime();
  const qrcodeUrl = `${baseURL}/room/qrcode?room_id=${this.data.roomId}&t=${timestamp}&token=${token}`;
  
  this.setData({ qrcodeUrl: qrcodeUrl });
}
```

### 2. 扫码进入房间

用户扫描小程序码后，小程序会自动打开 `pages/room/room` 页面，并传递场景值：

```javascript
onLoad(options) {
  let roomId = options.id;
  
  // 处理场景值（从小程序码进入）
  if (options.scene) {
    roomId = decodeURIComponent(options.scene);
    
    // 自动加入房间
    const userInfo = wx.getStorageSync('user_info');
    if (userInfo && (!userInfo.current_room_id || userInfo.current_room_id != roomId)) {
      this.autoJoinRoom(roomId);
    }
  }
  
  this.setData({ roomId: roomId });
}
```

### 3. 分享功能

支持两种分享方式：

**方式1：分享给好友**
```javascript
onShareAppMessage() {
  return {
    title: '邀请你加入房间一起玩！',
    path: `/pages/room/room?id=${this.data.roomId}`
  };
}
```

**方式2：分享到朋友圈**
```javascript
onShareTimeline() {
  return {
    title: '邀请你加入房间一起玩！',
    query: `id=${this.data.roomId}`
  };
}
```

## 使用流程

### 邀请流程

1. 用户在房间内点击"邀请"按钮
2. 弹窗显示小程序码
3. 用户可以：
   - 长按小程序码保存到相册
   - 点击"分享邀请"按钮分享给好友
4. 其他用户扫码或点击分享链接进入房间

### 扫码加入流程

1. 新用户扫描小程序码
2. 小程序自动打开房间页面
3. 解析场景值获取房间ID
4. 如果已登录，自动调用 `room.join` 接口加入房间
5. WebSocket自动连接，实时同步房间信息

## 常见问题

### 1. 小程序码不显示

**可能原因**：
- AppID和Secret配置错误
- 网络请求失败
- 图片加载失败

**解决方法**：
- 检查 `config/wechat.php` 配置
- 查看后端日志 `runtime/log/`
- 在开发者工具中查看 Network 请求

### 2. 扫码后无法进入房间

**可能原因**：
- 页面路径配置错误
- 场景值解析失败
- 用户未登录

**解决方法**：
- 确保 `page` 参数为 `pages/room/room`
- 检查场景值是否正确传递
- 添加登录状态检查

### 3. 小程序码生成失败

**错误信息**：
```json
{
  "errcode": 40001,
  "errmsg": "invalid credential"
}
```

**解决方法**：
- 检查 AppID 和 Secret 是否正确
- 确认 access_token 是否有效
- 查看微信服务器返回的错误信息

### 4. 开发环境注意事项

- 将 `env_version` 设置为 `'develop'`（开发版）
- 设置 `check_path: false` 避免路径检查
- 在微信开发者工具中测试扫码功能

### 5. 生产环境配置

发布前需要修改：
```php
'env_version' => 'release',  // 使用正式版
'check_path' => true,  // 启用路径检查
```

## 性能优化

1. **缓存机制**：24小时内重复请求直接返回缓存
2. **并发控制**：使用文件锁避免重复生成
3. **CDN加速**：可将生成的小程序码上传到CDN
4. **懒加载**：只在打开邀请弹窗时才加载小程序码

## 安全建议

1. ✅ Token认证：所有请求必须携带有效token
2. ✅ 房间验证：确认房间存在且状态为active
3. ✅ 权限控制：只有房间成员才能生成小程序码
4. ⚠️ Secret保护：不要将AppSecret提交到代码仓库
5. ⚠️ 访问限制：考虑添加频率限制避免滥用

## 微信官方文档

- [获取不限制的小程序码](https://developers.weixin.qq.com/miniprogram/dev/api-backend/open-api/qr-code/wxacode.getUnlimited.html)
- [小程序码场景值](https://developers.weixin.qq.com/miniprogram/dev/reference/scene-list.html)
- [获取 Access Token](https://developers.weixin.qq.com/miniprogram/dev/api-backend/open-api/access-token/auth.getAccessToken.html)

## 测试建议

1. 使用真机测试扫码功能（模拟器不支持扫码）
2. 测试不同网络环境下的加载速度
3. 验证缓存机制是否正常工作
4. 测试异常情况的错误处理

