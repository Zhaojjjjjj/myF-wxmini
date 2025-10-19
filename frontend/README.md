# 前端项目启动说明

## 环境要求
- 微信开发者工具

## 启动步骤

1. 打开微信开发者工具

2. 选择"导入项目"

3. 选择项目目录 `frontend/`

4. 填写AppID（如没有可选择测试号）

5. 点击"导入"即可运行项目

## 目录结构
```
frontend/
├── pages/         # 页面目录
│   ├── index/     # 首页
│   └── room/      # 房间页
├── app.js         # 小程序逻辑
├── app.json       # 小程序公共设置
└── project.config.json  # 项目配置文件
```

## 开发注意事项

1. 修改接口地址
   - 在 `pages/index/index.js` 和 `pages/room/room.js` 中修改 `https://your-domain.com` 为实际的后端地址

2. WebSocket地址
   - 在 `pages/room/room.js` 中修改 `wss://your-domain.com/ws` 为实际的WebSocket地址

3. 调试
   - 使用微信开发者工具的调试功能进行调试