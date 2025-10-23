// 引入API封装
const { room } = require("../../utils/api.js");
const app = getApp();

Page({
	data: {
		isLoggedIn: false,
		isLoggingIn: false,  // 正在登录中
		loginFailed: false,   // 登录失败
		pendingAction: null  // 保存待执行的操作
	},

	onLoad(options) {
		
		// 先检查本地是否已有登录信息
		const userInfo = wx.getStorageSync('user_info');
		if (userInfo && userInfo.token) {
			// 已有登录信息，直接标记为已登录
			this.setData({ 
				isLoggedIn: true,
				isLoggingIn: false,
				loginFailed: false
			});
			
			// 处理分享场景或检查房间状态
			if (options.room_id || options.id) {
				const roomId = options.room_id || options.id;
				this.navigateToRoom(roomId);
			} else {
				this.checkRoomStatus();
			}
			return;
		}
		
		// 标记为登录中
		this.setData({ 
			isLoggedIn: false,
			isLoggingIn: true,
			loginFailed: false
		});
		
		// 处理分享场景：如果从分享链接进入且带有房间ID
		if (options.room_id || options.id) {
			const roomId = options.room_id || options.id;
			
			// 保存房间ID到全局
			app.globalData.pendingRoomId = roomId;
			
			// 等待静默登录完成后自动进入房间
			app.waitForLogin((userInfo, error) => {
				if (userInfo) {
					// 登录成功，跳转到房间
					this.setData({ 
						isLoggedIn: true,
						isLoggingIn: false,
						loginFailed: false
					});
					this.navigateToRoom(roomId);
				} else {
					// 登录失败，提示用户
					this.setData({ 
						isLoggedIn: false,
						isLoggingIn: false,
						loginFailed: true
					});
					wx.showModal({
						title: '登录失败',
						content: '无法自动登录，请检查网络连接后重试',
						confirmText: '重试',
						cancelText: '取消',
						success: (res) => {
							if (res.confirm) {
								// 重新加载页面
								this.setData({ 
									isLoggingIn: true,
									loginFailed: false
								});
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
					this.setData({ 
						isLoggedIn: true,
						isLoggingIn: false,
						loginFailed: false
					});
					// 检查用户是否已在房间中
					this.checkRoomStatus();
				} else {
					this.setData({ 
						isLoggedIn: false,
						isLoggingIn: false,
						loginFailed: true
					});
				}
			});
		}
	},

	onShow() {
		// 每次显示时检查登录状态
		const userInfo = wx.getStorageSync('user_info');
		const hasToken = !!(userInfo && userInfo.token);
		this.setData({
			isLoggedIn: hasToken,
			isLoggingIn: hasToken ? false : this.data.isLoggingIn,
			loginFailed: hasToken ? false : this.data.loginFailed
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
					// 网络错误，清空本地房间ID
					userInfo.current_room_id = null;
					wx.setStorageSync("user_info", userInfo);
				});
		}
	},

	// 跳转到指定房间
	navigateToRoom(roomId) {
		wx.redirectTo({
			url: "/pages/room/room?id=" + roomId,
			fail: (err) => {
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
			app.globalData.pendingRoomId = null;  // 清除
		});
	},

	// 创建房间
	createRoom() {
		
		// 如果正在登录中，等待登录完成
		if (this.data.isLoggingIn) {
			wx.showLoading({
				title: '正在登录...',
				mask: true
			});
			
			// 等待登录完成
			app.waitForLogin((userInfo, error) => {
				wx.hideLoading();
				
				if (userInfo) {
					// 登录成功，继续创建房间
					this.setData({ 
						isLoggedIn: true,
						isLoggingIn: false,
						loginFailed: false
					});
					this.doCreateRoom();
				} else {
					// 登录失败
					this.setData({ 
						isLoggedIn: false,
						isLoggingIn: false,
						loginFailed: true
					});
					wx.showModal({
						title: '登录失败',
						content: '无法自动登录，请检查网络连接后重试',
						confirmText: '重试',
						cancelText: '取消',
						success: (res) => {
							if (res.confirm) {
								// 重试登录
								this.setData({ 
									isLoggingIn: true,
									loginFailed: false
								});
								app.silentLogin();
								// 登录成功后自动创建房间
								app.waitForLogin((retryUserInfo) => {
									if (retryUserInfo) {
										this.setData({ 
											isLoggedIn: true,
											isLoggingIn: false
										});
										this.doCreateRoom();
									}
								});
							}
						}
					});
				}
			});
			return;
		}
		
		// 检查本地是否有 token
		const localUserInfo = wx.getStorageSync('user_info');
		
		if (!localUserInfo || !localUserInfo.token) {
			this.setData({ 
				isLoggingIn: true,
				loginFailed: false
			});
			
			wx.showLoading({
				title: '正在登录...',
				mask: true
			});
			
			// 等待登录完成
			app.waitForLogin((userInfo, error) => {
				wx.hideLoading();
				
				if (userInfo) {
					// 登录成功，继续创建房间
					this.setData({ 
						isLoggedIn: true,
						isLoggingIn: false,
						loginFailed: false
					});
					this.doCreateRoom();
				} else {
					// 登录失败
					this.setData({ 
						isLoggedIn: false,
						isLoggingIn: false,
						loginFailed: true
					});
					wx.showModal({
						title: '登录失败',
						content: '无法自动登录，请检查网络连接后重试',
						confirmText: '重试',
						cancelText: '取消',
						success: (res) => {
							if (res.confirm) {
								// 重试
								this.createRoom();
							}
						}
					});
				}
			});
			return;
		}
		
		// 已登录，直接创建房间
		this.doCreateRoom();
	},
	
	// 执行创建房间操作
	doCreateRoom() {
		// 显示加载提示
		wx.showLoading({
			title: '创建中...',
			mask: true
		});

		// 创建房间
		room.create()
			.then((res) => {
				wx.hideLoading();
				
				if (res.code === 200) {
					// 更新本地存储的 current_room_id
					const localUserInfo = wx.getStorageSync('user_info');
					if (localUserInfo) {
						localUserInfo.current_room_id = res.data.room_id;
						wx.setStorageSync('user_info', localUserInfo);
					}
					
					// 跳转到房间页面
					wx.redirectTo({
						url: "/pages/room/room?id=" + res.data.room_id,
					});
				} else if (res.code === 401) {
					// token 失效，清除本地token并重新登录
					wx.removeStorageSync('user_info');
					this.setData({ 
						isLoggedIn: false,
						isLoggingIn: false,
						loginFailed: false
					});
					
					wx.showModal({
						title: '登录已过期',
						content: '需要重新登录',
						confirmText: '重新登录',
						showCancel: false,
						success: () => {
							// 自动重试
							this.createRoom();
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
				wx.showToast({
					title: "网络错误，请稍后重试",
					icon: "none",
				});
			});
	},
});
