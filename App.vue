<script>
	export default {
		onLaunch: function() {
			console.log('App Launch')
		},
		onShow: function() {
			console.log('App Show')
		},
		onHide: function() {
			console.log('App Hide')
		}
	}
</script>

<style>
	/*每个页面公共css */
	@import "colorui/main.css";
	@import "colorui/icon.css";
	.bg-blues{background: linear-gradient(to bottom right, #1e90ff, #00aaff);} 
	 .iconfont {
		 font-family: '微软雅黑';
	 }
	 i{font-style: normal;}
	 *{font-family: "微软雅黑";}
	input,textarea{font-size: 28rpx;}
</style>
