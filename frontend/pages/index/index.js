// 引入API封装
const { room } = require('../../utils/api.js');

Page({
  data: {
    hasRoom: false
  },

  onLoad() {
    this.checkRoomStatus();
  },

  // 检查用户是否在房间中
  checkRoomStatus() {
    wx.getStorage({
      key: 'user_info',
      success: (res) => {
        const user = res.data;
        if (user.current_room_id) {
          this.setData({ hasRoom: true });
          // 自动跳转到房间页面
          wx.redirectTo({
            url: '/pages/room/room?id=' + user.current_room_id
          });
        } else {
          this.setData({ hasRoom: false });
        }
      },
      fail: () => {
        this.setData({ hasRoom: false });
      }
    });
  },

  // 创建房间
  createRoom() {
    room.create()
      .then(res => {
        if (res.code === 200) {
          // 跳转到房间页面
          wx.redirectTo({
            url: '/pages/room/room?id=' + res.data.room_id
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
        console.error('创建房间失败', err);
      });
  },

  // 退出房间
  exitRoom() {
    wx.removeStorage({
      key: 'user_info',
      success: () => {
        this.setData({ hasRoom: false });
      }
    });
  }
});