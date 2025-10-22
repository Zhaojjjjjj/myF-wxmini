// 引入封装的请求函数
const { request, baseURL } = require('./utils/request.js');

App({
  onLaunch(options) {
    console.log('小程序启动', options);
    
    // 保存启动场景值，用于后续处理
    this.globalData.launchOptions = options;
    
    // 自动执行静默登录
    this.silentLogin();
  },

  onShow(options) {
    console.log('小程序显示', options);
    // 更新场景值
    this.globalData.launchOptions = options;
  },

  // 静默登录（自动执行，无需用户操作）
  silentLogin() {
    // 先检查本地是否有登录信息
    const userInfo = wx.getStorageSync('user_info');
    if (userInfo && userInfo.token) {
      this.globalData.userInfo = userInfo;
      this.globalData.isLoggedIn = true;
      console.log('用户已登录:', userInfo.nickname || userInfo.id);
      
      // 触发登录完成回调
      this.triggerLoginCallbacks(userInfo);
      return;
    }

    // 防止重复调用
    if (this.globalData.isLoggingIn) {
      console.log('正在登录中，请勿重复调用');
      return;
    }

    // 没有登录信息，执行静默登录
    console.log('开始静默登录...');
    this.globalData.isLoggingIn = true;
    this.globalData.loginError = null;

    wx.login({
      success: (res) => {
        if (res.code) {
          // 发送 code 到后台换取 token（静默，无需用户操作）
          request({
            url: '/user/login',
            method: 'POST',
            data: {
              code: res.code
            }
          }).then(loginRes => {
            if (loginRes.code === 200) {
              // 登录成功，保存基本信息
              const userInfo = loginRes.data;
              wx.setStorageSync('user_info', userInfo);
              this.globalData.userInfo = userInfo;
              this.globalData.isLoggedIn = true;
              this.globalData.isLoggingIn = false;
              
              console.log('静默登录成功:', userInfo.id);
              
              // 触发登录完成回调
              this.triggerLoginCallbacks(userInfo);
            } else {
              console.error('登录失败:', loginRes.msg);
              this.globalData.isLoggingIn = false;
              this.globalData.loginError = loginRes.msg;
              
              // 触发登录失败回调
              this.triggerLoginCallbacks(null, loginRes);
            }
          }).catch(err => {
            console.error('登录请求失败:', err);
            this.globalData.isLoggingIn = false;
            this.globalData.loginError = '网络错误';
            
            // 触发登录失败回调
            this.triggerLoginCallbacks(null, err);
          });
        } else {
          console.error('获取登录凭证失败:', res.errMsg);
          this.globalData.isLoggingIn = false;
          this.globalData.loginError = '获取登录凭证失败';
          
          // 触发登录失败回调
          this.triggerLoginCallbacks(null, { errMsg: res.errMsg });
        }
      },
      fail: (err) => {
        console.error('wx.login 调用失败:', err);
        this.globalData.isLoggingIn = false;
        this.globalData.loginError = 'wx.login失败';
        
        // 触发登录失败回调
        this.triggerLoginCallbacks(null, err);
      }
    });
  },

  // 等待登录完成
  waitForLogin(callback) {
    if (this.globalData.isLoggedIn) {
      // 已登录，直接执行回调
      callback(this.globalData.userInfo);
    } else if (this.globalData.isLoggingIn) {
      // 正在登录中，添加到回调队列，等待登录完成
      this.globalData.loginCallbacks.push(callback);
    } else {
      // 未登录且未在登录中
      // 添加到回调队列
      this.globalData.loginCallbacks.push(callback);
      
      // 清除之前的登录错误（如果有）
      this.globalData.loginError = null;
      
      // 尝试登录（silentLogin 内部会检查 isLoggingIn 标志，避免重复调用）
      this.silentLogin();
    }
  },

  // 触发登录完成回调
  triggerLoginCallbacks(userInfo, error) {
    const callbacks = this.globalData.loginCallbacks;
    this.globalData.loginCallbacks = [];
    
    callbacks.forEach(callback => {
      try {
        callback(userInfo, error);
      } catch (e) {
        console.error('登录回调执行失败:', e);
      }
    });
  },

  // 检查登录状态
  checkLoginStatus() {
    const userInfo = wx.getStorageSync('user_info');
    if (userInfo && userInfo.token) {
      this.globalData.userInfo = userInfo;
      this.globalData.isLoggedIn = true;
      return true;
    }
    return false;
  },

  // 执行登录（必须在用户交互中调用，如按钮点击）
  doLogin(successCallback, failCallback) {
    wx.showLoading({
      title: '登录中...',
      mask: true
    });

    wx.login({
      success: (res) => {
        if (res.code) {
          // 发送 code 到后台换取 token
          request({
            url: '/user/login',
            method: 'POST',
            data: {
              code: res.code
            }
          }).then(loginRes => {
            if (loginRes.code === 200) {
              // 登录成功，保存基本信息
              const userInfo = loginRes.data;
              wx.setStorageSync('user_info', userInfo);
              this.globalData.userInfo = userInfo;
              
              wx.hideLoading();
              wx.showToast({
                title: '登录成功',
                icon: 'success'
              });
              
              if (successCallback) {
                successCallback(userInfo);
              }
            } else {
              wx.hideLoading();
              wx.showToast({
                title: loginRes.msg || '登录失败',
                icon: 'none'
              });
              
              if (failCallback) {
                failCallback(loginRes);
              }
            }
          }).catch(err => {
            wx.hideLoading();
            wx.showToast({
              title: '网络错误',
              icon: 'none'
            });
            console.error('登录请求失败', err);
            
            if (failCallback) {
              failCallback(err);
            }
          });
        } else {
          wx.hideLoading();
          wx.showToast({
            title: '获取登录凭证失败',
            icon: 'none'
          });
          console.error('wx.login 失败', res.errMsg);
          
          if (failCallback) {
            failCallback(res);
          }
        }
      },
      fail: (err) => {
        wx.hideLoading();
        wx.showToast({
          title: '登录失败',
          icon: 'none'
        });
        console.error('wx.login 调用失败', err);
        
        if (failCallback) {
          failCallback(err);
        }
      }
    });
  },

  // 获取用户信息（需要用户授权）
  getUserProfile(successCallback, failCallback) {
    wx.getUserProfile({
      desc: '用于完善会员资料',
      success: (res) => {
        // 更新用户信息到后端
        request({
          url: '/user/update',
          method: 'POST',
          data: {
            nickname: res.userInfo.nickName,
            avatar_url: res.userInfo.avatarUrl
          }
        }).then(updateRes => {
          if (updateRes.code === 200) {
            // 更新本地存储
            const userInfo = updateRes.data;
            wx.setStorageSync('user_info', userInfo);
            this.globalData.userInfo = userInfo;
            
            if (successCallback) {
              successCallback(userInfo);
            }
          } else {
            if (failCallback) {
              failCallback(updateRes);
            }
          }
        }).catch(err => {
          console.error('更新用户信息失败', err);
          if (failCallback) {
            failCallback(err);
          }
        });
      },
      fail: (err) => {
        console.error('获取用户信息失败', err);
        if (failCallback) {
          failCallback(err);
        }
      }
    });
  },

  // 检查是否已登录，未登录则引导登录
  requireLogin(successCallback, cancelCallback) {
    const userInfo = wx.getStorageSync('user_info');
    if (userInfo && userInfo.token) {
      // 已登录
      if (successCallback) {
        successCallback(userInfo);
      }
      return true;
    } else {
      // 未登录，显示登录提示
      wx.showModal({
        title: '需要登录',
        content: '请先登录后再继续操作',
        confirmText: '立即登录',
        cancelText: '暂不登录',
        success: (res) => {
          if (res.confirm) {
            // 用户点击登录
            this.doLogin(successCallback, cancelCallback);
          } else {
            // 用户取消登录
            if (cancelCallback) {
              cancelCallback();
            }
          }
        }
      });
      return false;
    }
  },

  globalData: {
    userInfo: null,
    baseURL: baseURL,
    launchOptions: null,
    pendingRoomId: null,  // 用于保存待进入的房间ID
    isLoggedIn: false,    // 登录状态标志
    isLoggingIn: false,   // 正在登录中标志
    loginError: null,     // 登录错误信息
    loginCallbacks: []    // 登录完成回调队列
  }
});
