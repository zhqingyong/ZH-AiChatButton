<template>
	<view class="content">
		<image class="logo" src="/static/logo.png"></image>
		<view class="text-area">
			<text class="title">{{title}}</text>
		</view>
		
		<!-- AI智能助手组件 -->
		<ai-chat :pageType="'me'"></ai-chat>
	</view>
</template>

<script>
	import AiChat from '@/components/ZH-AiChatButton/ZH-AiChatButton.vue'
	
	export default {
		components: {
			AiChat
		}, // AI智能助手组件
		
		data() {
			return {
				title: '关于'
			}
		},
		
		onShow(){
			// 通知AI管理器当前页面状态
			if (this.$aiFloatManager) {
				this.$aiFloatManager.setActivePageType('me');
			}
		},
		
		onLoad() {

		},
		methods: {

		}
	}
</script>

<style>
	.content {
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
	}

	.logo {
		height: 200rpx;
		width: 200rpx;
		margin-top: 200rpx;
		margin-left: auto;
		margin-right: auto;
		margin-bottom: 50rpx;
	}

	.text-area {
		display: flex;
		justify-content: center;
	}

	.title {
		font-size: 36rpx;
		color: #8f8f94;
	}
</style>
