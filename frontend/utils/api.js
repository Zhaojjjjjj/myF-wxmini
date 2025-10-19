// API接口封装
const { request } = require('./request');

// 用户相关接口
const user = {
  // 获取用户信息
  getInfo: () => {
    return request({
      url: '/user/info',
      method: 'GET'
    });
  },
  
  // 更新用户信息
  update: (data) => {
    return request({
      url: '/user/update',
      method: 'POST',
      data
    });
  }
};

// 房间相关接口
const room = {
  // 创建房间
  create: () => {
    return request({
      url: '/room/create',
      method: 'POST'
    });
  },
  
  // 加入房间
  join: (data) => {
    return request({
      url: '/room/join',
      method: 'POST',
      data
    });
  },
  
  // 获取房间详情
  detail: (data) => {
    return request({
      url: '/room/detail',
      method: 'GET',
      data
    });
  },
  
  // 退出房间
  exit: (data) => {
    return request({
      url: '/room/exit',
      method: 'POST',
      data
    });
  },
  
  // 获取房间小程序码
  getQrCode: (data) => {
    return request({
      url: '/room/qrcode',
      method: 'GET',
      data
    });
  }
};

// 转分相关接口
const transfer = {
  // 转分
  transfer: (data) => {
    return request({
      url: '/transfer',
      method: 'POST',
      data
    });
  }
};

module.exports = {
  user,
  room,
  transfer
};