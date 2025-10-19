// 引入API封装
const { transfer, room, user } = require('../../utils/api.js');
const { connectWebSocket, request, baseURL } = require('../../utils/request.js');

Page({
  data: {
    roomId: null,
    members: [],
    logs: [],
    showInviteModal: false,
    showTransferModal: false,
    showProfileModal: false,
    selectedUserId: null,
    transferAmount: 0,
    newNickname: '',
    qrcodeUrl: '',
    currentUserId: null,
    currentUserAvatar: '', // 当前用户头像
    tempAvatarFile: null   // 临时头像文件
  },

  onLoad(options) {
    // 获取当前用户ID和头像
    const userInfo = wx.getStorageSync('user_info');
    if (userInfo) {
      this.setData({
        currentUserId: userInfo.id,
        currentUserAvatar: userInfo.avatar_url || ''
      });
    }

    this.setData({ roomId: options.id });
    this.connectWebSocket();
    this.refreshRoom();
    this.loadQrCode(); // 加载小程序码
  },

  // 连接WebSocket
  connectWebSocket() {
    connectWebSocket(this.data.roomId)
      .then(() => {
        console.log('WebSocket连接已建立');
      })
      .catch(err => {
        console.error('WebSocket连接失败', err);
      });
    
    // 监听WebSocket消息
    wx.onSocketMessage((res) => {
      const data = JSON.parse(res.data);
      switch (data.type) {
        case 'room_update':
          this.setData({ members: data.members });
          break;
        case 'log_update':
          this.setData({ logs: [...this.data.logs, data.log] });
          break;
        case 'room_closed':
          wx.showToast({
            title: '房间已关闭',
            icon: 'none'
          });
          setTimeout(() => {
            wx.redirectTo({
              url: '/pages/index/index'
            });
          }, 1500);
          break;
      }
    });
    
    // 监听WebSocket关闭
    wx.onSocketClose(() => {
      console.log('WebSocket连接已断开');
    });
    
    // 监听WebSocket错误
    wx.onSocketError((err) => {
      console.error('WebSocket连接错误', err);
    });
  },

  // 刷新房间数据
  refreshRoom() {
    // 调用后端API获取最新房间数据
    room.detail({ room_id: this.data.roomId })
      .then(res => {
        if (res.code === 200) {
          this.setData({
            members: res.data.members,
            logs: res.data.logs
          });
        } else {
          wx.showToast({
            title: res.msg,
            icon: 'none'
          });
        }
      })
      .catch(err => {
        wx.showToast({
          title: '网络错误',
          icon: 'none'
        });
        console.error('获取房间数据失败', err);
      });
  },

  // 加载小程序码
  loadQrCode() {
    // 直接设置小程序码的URL
    const qrcodeUrl = `${baseURL}/room/qrcode?room_id=${this.data.roomId}`;
    this.setData({
      qrcodeUrl: qrcodeUrl
    });
  },

  // 显示邀请弹窗
  showInviteModal() {
    this.setData({ showInviteModal: true });
  },

  // 隐藏邀请弹窗
  hideInviteModal() {
    this.setData({ showInviteModal: false });
  },

  // 成员点击事件
  onMemberTap(e) {
    const userId = e.currentTarget.dataset.userId;
    const userNickname = e.currentTarget.dataset.userNickname;
    const userAvatar = e.currentTarget.dataset.userAvatar;

    // 如果点击的是自己，则弹出修改资料弹窗
    if (userId === this.data.currentUserId) {
      this.setData({
        showProfileModal: true,
        newNickname: userNickname,
        currentUserAvatar: userAvatar
      });
    } else {
      // 否则弹出转分弹窗
      this.setData({
        showTransferModal: true,
        selectedUserId: userId
      });
    }
  },

  // 转分金额输入
  onTransferAmountInput(e) {
    this.setData({ transferAmount: e.detail.value });
  },

  // 确认转账
  confirmTransfer() {
    if (this.data.transferAmount <= 0 || this.data.transferAmount > 10000) {
      wx.showToast({
        title: '转账金额必须在1-10000之间',
        icon: 'none'
      });
      return;
    }
    
    transfer.transfer({
      to_user_id: this.data.selectedUserId,
      amount: this.data.transferAmount
    })
      .then(res => {
        if (res.code === 200) {
          wx.showToast({
            title: '转账成功',
            icon: 'success'
          });
          this.hideTransferModal();
          // 转账成功后刷新房间数据
          this.refreshRoom();
        } else {
          wx.showToast({
            title: res.msg,
            icon: 'none'
          });
        }
      })
      .catch(err => {
        wx.showToast({
          title: '网络错误',
          icon: 'none'
        });
        console.error('转账失败', err);
      });
  },

  // 选择头像
  chooseAvatar() {
    wx.chooseImage({
      count: 1,
      sizeType: ['original', 'compressed'],
      sourceType: ['album', 'camera'],
      success: (res) => {
        const tempFilePath = res.tempFilePaths[0];
        this.setData({
          tempAvatarFile: tempFilePath,
          currentUserAvatar: tempFilePath // 临时显示选择的头像
        });
      },
      fail: (err) => {
        console.error('选择头像失败', err);
        wx.showToast({
          title: '选择头像失败',
          icon: 'none'
        });
      }
    });
  },

  // 昵称输入
  onNicknameInput(e) {
    this.setData({ newNickname: e.detail.value });
  },

  // 显示修改资料弹窗
  showProfileModal() {
    this.setData({ showProfileModal: true });
  },

  // 隐藏修改资料弹窗
  hideProfileModal() {
    this.setData({
      showProfileModal: false,
      tempAvatarFile: null // 清空临时头像文件
    });
  },

  // 确认更新资料
  confirmUpdateProfile() {
    const updateData = {
      nickname: this.data.newNickname
    };

    // 如果有选择新头像，先上传头像
    if (this.data.tempAvatarFile) {
      this.uploadAvatarAndUpdate(updateData);
    } else {
      // 没有选择新头像，直接更新昵称
      this.updateUserProfile(updateData);
    }
  },

  // 上传头像并更新资料
  uploadAvatarAndUpdate(updateData) {
    // 获取用户token
    const userInfo = wx.getStorageSync('user_info');
    const token = userInfo ? userInfo.token : '';

    wx.uploadFile({
      url: `${baseURL}/user/avatar`,
      filePath: this.data.tempAvatarFile,
      name: 'avatar',
      header: {
        'Authorization': token
      },
      success: (res) => {
        try {
          const data = JSON.parse(res.data);
          if (data.code === 200) {
            // 头像上传成功，添加到更新数据中
            updateData.avatar_url = data.data.avatar_url;
            this.updateUserProfile(updateData);
          } else {
            wx.showToast({
              title: data.msg || '头像上传失败',
              icon: 'none'
            });
          }
        } catch (e) {
          wx.showToast({
            title: '头像上传失败',
            icon: 'none'
          });
        }
      },
      fail: (err) => {
        wx.showToast({
          title: '头像上传失败',
          icon: 'none'
        });
        console.error('头像上传失败', err);
      }
    });
  },

  // 更新用户资料
  updateUserProfile(updateData) {
    user.update(updateData)
      .then(res => {
        if (res.code === 200) {
          wx.showToast({
            title: '更新成功',
            icon: 'success'
          });
          this.hideProfileModal();
          // 更新成功后刷新房间数据
          this.refreshRoom();
          // 更新本地存储的用户信息
          wx.setStorageSync('user_info', res.data);
        } else {
          wx.showToast({
            title: res.msg,
            icon: 'none'
          });
        }
      })
      .catch(err => {
        wx.showToast({
          title: '网络错误',
          icon: 'none'
        });
        console.error('更新资料失败', err);
      });
  },

  // 退出房间
  exitRoom() {
    wx.showModal({
      title: '确认退出',
      content: '确定要退出房间吗？',
      success: (res) => {
        if (res.confirm) {
          room.exit({ room_id: this.data.roomId })
            .then(res => {
              if (res.code === 200) {
                wx.showToast({
                  title: '退出成功',
                  icon: 'success'
                });
                // 立即跳转到首页，而不是等待1.5秒
                wx.redirectTo({
                  url: '/pages/index/index'
                });
              } else {
                wx.showToast({
                  title: res.msg,
                  icon: 'none'
                });
              }
            })
            .catch(err => {
              wx.showToast({
                title: '网络错误',
                icon: 'none'
              });
              console.error('退出房间失败', err);
            });
        }
      }
    });
  }
});