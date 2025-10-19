// 封装请求函数
const config = {
  // 本地开发环境
  development: {
    baseURL: 'http://score.lo:81',
    wsURL: 'ws://score.lo:9501'
  },
  // 生产环境
  production: {
    baseURL: 'https://your-production-domain.com',
    wsURL: 'wss://your-production-domain.com/ws'
  }
};

// 简化环境判断，直接使用开发环境配置
// 在微信小程序中，我们通过其他方式来区分环境
const env = 'development';

// 获取对应环境的配置
const envConfig = config[env] || config.development;

// 导出基础URL和WebSocket URL
const baseURL = envConfig.baseURL;
const wsURL = envConfig.wsURL;

// 封装请求函数
const request = (options) => {
  // 获取用户token
  const userInfo = wx.getStorageSync('user_info');
  const token = userInfo ? userInfo.token : '';

  return new Promise((resolve, reject) => {
    wx.request({
      url: baseURL + options.url,
      method: options.method || 'GET',
      data: options.data || {},
      header: {
        'content-type': 'application/json',
        'Authorization': token, // 添加认证头
        ...options.header
      },
      success: (res) => {
        if (res.statusCode === 200) {
          resolve(res.data);
        } else {
          reject(res);
        }
      },
      fail: (err) => {
        reject(err);
      }
    });
  });
};

// 封装WebSocket连接函数
const connectWebSocket = (roomId) => {
  return new Promise((resolve, reject) => {
    const url = `${wsURL}?room_id=${roomId}`;
    wx.connectSocket({
      url: url,
      success: () => {
        resolve();
      },
      fail: (err) => {
        reject(err);
      }
    });
  });
};

module.exports = {
  request,
  connectWebSocket,
  baseURL,
  wsURL
};