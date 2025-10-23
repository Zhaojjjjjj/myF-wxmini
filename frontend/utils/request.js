// 封装请求函数
const config = {
	// 本地开发环境
	development: {
		baseURL: "http://score.lo:81",
		wsURL: "ws://score.lo:9501",
	},
	// 生产环境
	production: {
		baseURL: "https://wx.0326j.top",
		wsURL: "wss://wx.0326j.top",
	},
};

// 自动判断小程序运行环境
const accountInfo = wx.getAccountInfoSync();
const envVersion = accountInfo.miniProgram.envVersion;

let env = "development";
if (envVersion === "release" || envVersion === "trial") {
	env = "production"; // 体验版与正式版都用生产接口
}

// 获取对应环境的配置
const envConfig = config[env];
const baseURL = envConfig.baseURL;
const wsURL = envConfig.wsURL;

// 封装请求函数
const request = (options) => {
	const userInfo = wx.getStorageSync("user_info");
	const token = userInfo && userInfo.token ? userInfo.token : "";

	return new Promise((resolve, reject) => {
		wx.request({
			url: baseURL + options.url,
			method: options.method || "GET",
			data: options.data || {},
			header: {
				"content-type": "application/json",
				Authorization: token,
				...options.header,
			},
			success: (res) => {
				if (res.statusCode === 200) {
					// 如果返回401，说明token失效，清除本地登录信息
					if (res.data && res.data.code === 401) {
						wx.removeStorageSync('user_info');
						wx.showToast({
							title: res.data.msg || '登录已过期，请重新登录',
							icon: 'none'
						});
					}
					resolve(res.data);
				} else {
					reject(res);
				}
			},
			fail: (err) => {
				reject(err);
			},
		});
	});
};

// 封装WebSocket连接函数
const connectWebSocket = (roomId) => {
	return new Promise((resolve, reject) => {
		const url = `${wsURL}?room_id=${roomId}`;
		wx.connectSocket({
			url,
			success: resolve,
			fail: reject,
		});
	});
};

module.exports = {
	request,
	connectWebSocket,
	baseURL,
	wsURL,
};
