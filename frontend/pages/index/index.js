// 引入API封装
const { room } = require("../../utils/api.js");

Page({
	data: {},

	onLoad() {
		this.checkRoomStatus();
	},

	// 检查用户是否在房间中
	checkRoomStatus() {
		wx.getStorage({
			key: "user_info",
			success: (res) => {
				const user = res.data;
				if (user.current_room_id) {
					// 验证房间是否仍然存在
					room.detail({ room_id: user.current_room_id })
						.then((res) => {
							if (res.code === 200) {
								// 房间存在，跳转到房间页面
								wx.redirectTo({
									url: "/pages/room/room?id=" + user.current_room_id,
								});
							} else {
								// 房间不存在，清空本地房间ID
								user.current_room_id = null;
								wx.setStorageSync("user_info", user);
							}
						})
						.catch((err) => {
							// 网络错误，清空本地房间ID
							user.current_room_id = null;
							wx.setStorageSync("user_info", user);
						});
				}
			},
		});
	},

	// 创建房间
	createRoom() {
		room.create()
			.then((res) => {
				if (res.code === 200) {
					// 跳转到房间页面
					wx.redirectTo({
						url: "/pages/room/room?id=" + res.data.room_id,
					});
				} else {
					wx.showToast({
						title: res.msg,
						icon: "none",
					});
				}
			})
			.catch((err) => {
				wx.showToast({
					title: "网络错误" + BASE,
					icon: "none",
					duration: 5000,
				});
				console.error("创建房间失败", err);
			});
	},
});
