// 引入封装的请求函数
const { request, baseURL } = require('./utils/request.js');

App({
  onLaunch() {
    // 初始化时检查用户是否已登录
    this.checkLogin();
  },

  checkLogin() {
    // 检查本地存储中是否有用户信息
    const userInfo = wx.getStorageSync('user_info');
    if (!userInfo) {
      // 如果没有用户信息，尝试通过微信登录获取
      this.login();
    }
  },

  login() {
    wx.login({
      success: (res) => {
        if (res.code) {
          // 发送 res.code 到后台换取 openId, sessionKey, unionId
          request({
            url: '/user/login',
            method: 'POST',
            data: {
              code: res.code
            }
          }).then(res => {
            if (res.code === 200) {
              // 登录成功，获取微信用户信息
              wx.getUserProfile({
                desc: '用于完善会员资料',
                success: (profileRes) => {
                  // 更新用户信息
                  request({
                    url: '/user/update',
                    method: 'POST',
                    data: {
                      nickname: profileRes.userInfo.nickName,
                      avatar_url: profileRes.userInfo.avatarUrl
                    }
                  }).then(updateRes => {
                    if (updateRes.code === 200) {
                      // 更新成功，保存用户信息到本地存储
                      wx.setStorageSync('user_info', updateRes.data);
                    } else {
                      console.error('更新用户信息失败', updateRes.msg);
                      // 即使更新失败，也保存基本的登录信息
                      wx.setStorageSync('user_info', res.data);
                    }
                  }).catch(updateErr => {
                    console.error('更新用户信息请求失败', updateErr);
                    // 即使更新失败，也保存基本的登录信息
                    wx.setStorageSync('user_info', res.data);
                  });
                },
                fail: () => {
                  // 用户拒绝授权，保存基本的登录信息
                  wx.setStorageSync('user_info', res.data);
                }
              });
            } else {
              console.error('登录失败', res.msg);
            }
          }).catch(err => {
            console.error('登录请求失败', err);
          });
        } else {
          console.log('登录失败！' + res.errMsg);
        }
      }
    });
  },

  globalData: {
    userInfo: null,
    baseURL: baseURL
  }
});