// 引入API封装
const { room } = require("../../utils/api.js");
const app = getApp();

Page({
	data: {
		isLoggedIn: false,
		pendingAction: null  // 保存待执行的操作
	},

	onLoad(options) {
		console.log('首页加载，参数:', options);
		
		// 处理分享场景：如果从分享链接进入且带有房间ID
		if (options.room_id || options.id) {
			const roomId = options.room_id || options.id;
			console.log('从分享进入，房间ID:', roomId);
			
			// 保存房间ID到全局
			app.globalData.pendingRoomId = roomId;
			
			// 显示加载提示
			wx.showLoading({
				title: '加载中...',
				mask: true
			});
			
			// 等待静默登录完成后自动进入房间
			app.waitForLogin((userInfo, error) => {
				wx.hideLoading();
				
				if (userInfo) {
					// 登录成功，跳转到房间
					this.setData({ isLoggedIn: true });
					this.navigateToRoom(roomId);
				} else {
					// 登录失败，提示用户
					this.setData({ isLoggedIn: false });
					wx.showModal({
						title: '登录失败',
						content: '无法自动登录，请检查网络连接后重试',
						confirmText: '重试',
						cancelText: '取消',
						success: (res) => {
							if (res.confirm) {
								// 重新加载页面
								app.silentLogin();
								setTimeout(() => {
									this.onLoad(options);
								}, 500);
							} else {
								app.globalData.pendingRoomId = null;
							}
						}
					});
				}
			});
		} else {
			// 正常进入首页，等待静默登录完成后检查房间状态
			app.waitForLogin((userInfo, error) => {
				if (userInfo) {
					this.setData({ isLoggedIn: true });
					// 检查用户是否已在房间中
					this.checkRoomStatus();
				} else {
					this.setData({ isLoggedIn: false });
					console.log('静默登录失败，但允许浏览首页');
				}
			});
		}
	},

	onShow() {
		// 每次显示时检查登录状态
		const userInfo = wx.getStorageSync('user_info');
		this.setData({
			isLoggedIn: !!(userInfo && userInfo.token)
		});
	},

	// 检查用户是否在房间中
	checkRoomStatus() {
		const userInfo = wx.getStorageSync('user_info');
		if (userInfo && userInfo.token && userInfo.current_room_id) {
			// 验证房间是否仍然存在
			room.detail({ room_id: userInfo.current_room_id })
				.then((res) => {
					if (res.code === 200) {
						// 房间存在，跳转到房间页面
						wx.redirectTo({
							url: "/pages/room/room?id=" + userInfo.current_room_id,
						});
					} else {
						// 房间不存在，清空本地房间ID
						userInfo.current_room_id = null;
						wx.setStorageSync("user_info", userInfo);
					}
				})
				.catch((err) => {
					console.error('检查房间状态失败', err);
					// 网络错误，清空本地房间ID
					userInfo.current_room_id = null;
					wx.setStorageSync("user_info", userInfo);
				});
		}
	},

	// 跳转到指定房间
	navigateToRoom(roomId) {
		console.log('跳转到房间:', roomId);
		wx.redirectTo({
			url: "/pages/room/room?id=" + roomId,
			fail: (err) => {
				console.error('跳转失败', err);
				wx.showToast({
					title: '跳转失败',
					icon: 'none'
				});
			}
		});
	},

	// 处理登录
	handleLogin() {
		app.doLogin((userInfo) => {
			// 登录成功
			this.setData({
				isLoggedIn: true
			});
			
			// 如果有待进入的房间，登录后自动进入
			if (app.globalData.pendingRoomId) {
				const roomId = app.globalData.pendingRoomId;
				app.globalData.pendingRoomId = null;  // 清除
				
				// 延迟一下，让登录成功提示显示出来
				setTimeout(() => {
					this.navigateToRoom(roomId);
				}, 500);
			}
		}, (err) => {
			// 登录失败
			console.error('登录失败', err);
			app.globalData.pendingRoomId = null;  // 清除
		});
	},

	// 创建房间
	createRoom() {
		console.log('创建房间按钮被点击');
		
		// 检查本地是否有 token
		const localUserInfo = wx.getStorageSync('user_info');
		console.log('本地用户信息:', localUserInfo);
		
		if (!localUserInfo || !localUserInfo.token) {
			console.log('未登录或 token 不存在，提示用户登录');
			wx.showModal({
				title: '需要登录',
				content: '请先登录后再创建房间',
				confirmText: '立即登录',
				success: (res) => {
					if (res.confirm) {
						this.handleLogin();
					}
				}
			});
			return;
		}
		
		// 已有 token，显示加载提示
		wx.showLoading({
			title: '创建中...',
			mask: true
		});

		// 创建房间
		room.create()
			.then((res) => {
				wx.hideLoading();
				console.log('创建房间响应:', res);
				
				if (res.code === 200) {
					// 更新本地存储的 current_room_id
					localUserInfo.current_room_id = res.data.room_id;
					wx.setStorageSync('user_info', localUserInfo);
					
					// 跳转到房间页面
					wx.redirectTo({
						url: "/pages/room/room?id=" + res.data.room_id,
					});
				} else if (res.code === 401) {
					// token 失效，提示重新登录
					console.log('Token 失效，提示重新登录');
					wx.showModal({
						title: '登录已过期',
						content: '请重新登录后再试',
						confirmText: '立即登录',
						success: (modalRes) => {
							if (modalRes.confirm) {
								this.handleLogin();
							}
						}
					});
				} else {
					wx.showToast({
						title: res.msg || '创建失败',
						icon: "none",
					});
				}
			})
			.catch((err) => {
				wx.hideLoading();
				console.error("创建房间失败", err);
				wx.showToast({
					title: "网络错误，请稍后重试",
					icon: "none",
				});
			});
	},
});
