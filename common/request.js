// 示例请求拦截器，根据需要保留或者删除、修改
export default{
	common:{
		baseUrl:"http://192.168.0.1:8888/api", //示例地址，请改为自己的地址
		data:{},
		header:{
			// "content-type": "application/json",
			"content-type": "application/x-www-form-urlencoded"
		},
		method:"POST",
		dataType:"json"
	},
	request( options={} ){
		options.url = this.common.baseUrl + options.url;
		options.data = options.data || this.common.data;
		options.header = options.header || this.common.header;
		options.method = options.method || this.common.method;
		options.dataType = options.dataType || this.common.dataType;
		return new Promise((res,err)=>{
			uni.request({
				...options,
				success: (result) => {
					if (result.statusCode != 200) {
						return;
					}
					let data = result.data;
					res(data)
				}
			})
			
		})
	}
}

