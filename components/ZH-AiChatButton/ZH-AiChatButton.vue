<template>
	<view>
		<!-- AI智能助手浮动按钮 -->
		<view 
			class="ai-float-btn" 
			:style="{top: floatPosition.top + 'px', right: floatPosition.right + 'px'}" 
			@touchstart="handleTouchStart" 
			@touchmove="handleTouchMove"
			@touchend="handleTouchEnd" 
			@click="showAIChat" 
			v-show="showFloatBtn"
		>
			<view class="ai-icon">
				<text class="cuIcon-service"></text>
				<text class="ai-label">AI助手</text>
			</view>
			<view class="ai-pulse"></view>
		</view>

		<!-- AI聊天弹窗 - 现代浮动圆角设计 -->
		<uni-popup ref="aiChatPopup" type="center" :safe-area="false" :mask-click="false">
			<view class="ai-chat-modal" @click.stop>
				<view class="chat-container">
					<!-- 顶部标题栏 -->
					<view class="chat-header">
						<view class="header-left">
							<view class="ai-avatar">
								<text class="cuIcon-service"></text>
								<view class="online-indicator"></view>
							</view>
							<view class="header-info">
								<text class="chat-title">AI智能助手</text>
								<text class="chat-subtitle">{{ getSubtitle() }}</text>
							</view>
						</view>
						<view class="header-actions">
							<view class="clear-btn" @click="showClearConfirm">
								<text class="cuIcon-delete"></text>
							</view>
							<view class="minimize-btn" @click="minimizeChat">
								<text class="cuIcon-fold"></text>
							</view>
							<view class="close-btn" @click="closeAIChat">
								<text class="cuIcon-close"></text>
							</view>
						</view>
					</view>
					
					<!-- 聊天内容区域 -->
					<view class="chat-content">
						<scroll-view 
							class="message-list" 
							scroll-y 
							:scroll-top="scrollTop"
							:scroll-with-animation="false"
							:enable-flex="true"
							:show-scrollbar="false"
							:enhanced="true"
							:bounces="false"
							:fast-deceleration="false"
						>
							<!-- 欢迎消息 -->
							<view class="welcome-message" v-if="chatHistory.length === 0">
								<view class="welcome-icon">
									<view class="icon-bg">
										<text class="cuIcon-service"></text>
									</view>
									<view class="icon-glow"></view>
								</view>
								<text class="welcome-title">{{ getWelcomeTitle() }}</text>
								<text class="welcome-text">{{ getWelcomeText() }}</text>
								
								<!-- 重试按钮（如果有失败的消息） -->
								<view class="retry-section" v-if="lastFailedMessage">
									<view class="retry-btn" @click="retryLastMessage">
										<text class="cuIcon-refresh"></text>
										<text>重试上次问题</text>
									</view>
								</view>
								
								<!-- 快捷问题建议 -->
								<view class="quick-questions">
									<view 
										class="quick-item" 
										v-for="(question, index) in getQuickQuestions()" 
										:key="index"
										@click="sendQuickMessage(question)"
									>
										<text>{{ question }}</text>
									</view>
								</view>
							</view>
							
							<!-- 聊天消息 -->
							<view 
								class="message-item" 
								v-for="(message, index) in chatHistory" 
								:key="index"
								:class="message.type"
							>
								<!-- AI消息：头像在左侧，内容在右侧 -->
								<template v-if="message.type === 'ai'">
									<view class="message-avatar">
										<text class="cuIcon-service"></text>
									</view>
									<view class="message-content">
										<view class="message-bubble">
											<text class="message-text">{{ message.content }}</text>
										</view>
										<text class="message-time">{{ message.time }}</text>
									</view>
								</template>
								
								<!-- 用户消息：内容在左侧，头像在右侧 -->
								<template v-if="message.type === 'user'">
									<view class="message-content">
										<view class="message-bubble">
											<text class="message-text">{{ message.content }}</text>
										</view>
										<text class="message-time">{{ message.time }}</text>
									</view>
									<view class="message-avatar user-avatar">
										<text class="cuIcon-people"></text>
									</view>
								</template>
							</view>
							
							<!-- AI思考中状态 -->
							<view class="message-item ai thinking-message" v-if="isLoading">
								<view class="message-avatar">
									<text class="cuIcon-service"></text>
								</view>
								<view class="message-content">
									<view class="thinking-bubble">
										<view class="thinking-dots">
											<span></span>
											<span></span>
											<span></span>
										</view>
										<text class="thinking-text">AI正在思考中...</text>
									</view>
								</view>
							</view>
						</scroll-view>
					</view>
					
					<!-- 输入区域 -->
					<view class="chat-input">
						<view class="input-container">
							<view class="input-wrapper">
								<input 
									class="message-input" 
									type="text" 
									placeholder="请输入您的问题..."
									v-model="inputMessage"
									@confirm="sendMessage"
									:disabled="isLoading"
									:adjust-position="true"
									:cursor-spacing="10"
								/>
								<view class="input-actions">
									<!-- <view class="voice-btn" @click="toggleVoice">
										<text class="cuIcon-voice"></text>
									</view> -->
									<view 
										class="send-btn" 
										:class="{'active': inputMessage.trim() && !isLoading}"
										@click="sendMessage"
									>
										<text class="cuIcon-right"></text>
									</view>
								</view>
							</view>
						</view>
					</view>
				</view>
			</view>
		</uni-popup>

		<!-- 清空聊天记录确认弹窗 -->
		<uni-popup ref="clearConfirmPopup" type="center">
			<view class="clear-confirm-modal">
				<view class="confirm-content">
					<view class="confirm-icon">
						<text class="cuIcon-warn"></text>
					</view>
					<text class="confirm-title">清空聊天记录</text>
					<text class="confirm-message">确定要清空当前会话的所有聊天记录吗？此操作不可恢复。</text>
					<view class="confirm-actions">
						<view class="cancel-btn" @click="closeClearConfirm">
							<text>取消</text>
						</view>
						<view class="confirm-btn" @click="clearChatHistory">
							<text>确定</text>
						</view>
					</view>
				</view>
			</view>
		</uni-popup>
	</view>
</template>

<script>
import aiFloatManager from '@/common/aiFloatManager.js';

export default {
	name: 'AiChat',
	props: {
		// 当前页面类型，用于定制化服务
		pageType: {
			type: String,
			default: 'general' // general, index, me, data
		}
	},
	data() {
		return {
			showFloatBtn: true,
			floatPosition: {
				top: 300,
				right: 15
			},
			isDragging: false,
			startY: 0,
			startX: 0,
			chatHistory: [],
			inputMessage: '',
			isLoading: false,
			scrollTop: 0,
			lastFailedMessage: '', // 保存最后失败的消息，用于重试
			unsubscribePosition: null, // 存储取消订阅函数
			unsubscribeChatState: null, // 存储聊天状态取消订阅函数
			chatWindowVisible: false // 本地聊天窗口可见状态
		}
	},
	watch: {
		// 监听聊天历史变化，确保新消息自动滚动到底部
		chatHistory: {
			handler(newHistory, oldHistory) {
				if (newHistory.length > oldHistory.length) {
					// 有新消息时，延迟滚动确保内容完全渲染
					this.$nextTick(() => {
						setTimeout(() => {
							this.scrollToBottom();
						}, 60);
						
						// 双重保险滚动
						setTimeout(() => {
							this.scrollToBottom();
						}, 150);
					});
				}
			},
			deep: true
		},
		
		// 监听思考状态变化，立即滚动
		isLoading(newVal) {
			if (newVal) {
				// 思考状态开始时，确保思考气泡能显示在底部
				this.$nextTick(() => {
					// 短暂延迟确保思考气泡已渲染
					setTimeout(() => {
						this.scrollToBottom();
					}, 80);
					
					// 额外保险滚动
					setTimeout(() => {
						this.scrollToBottom();
					}, 200);
					
					setTimeout(() => {
						this.scrollToBottom();
					}, 400);
				});
			}
		}
	},
	mounted() {
		// 订阅全局位置管理器
		this.subscribeToPositionChanges();
		// 订阅聊天窗口状态变化
		this.subscribeToChatStateChanges();
		// 设置当前页面为活跃页面
		aiFloatManager.setActivePageType(this.pageType);
	},
	
	beforeDestroy() {
		// 取消订阅，避免内存泄漏
		if (this.unsubscribePosition) {
			this.unsubscribePosition();
			this.unsubscribePosition = null;
		}
		if (this.unsubscribeChatState) {
			this.unsubscribeChatState();
			this.unsubscribeChatState = null;
		}
	},
	methods: {
		// 获取页面相关的副标题
		getSubtitle() {
			const subtitles = {
				index: '设备管理助手',
				me: '个人中心助手', 
				data: '数据分析助手',
				general: '智能助手'
			};
			return subtitles[this.pageType] || subtitles.general;
		},
		
		// 获取欢迎标题
		getWelcomeTitle() {
			const titles = {
				index: '欢迎使用设备管理AI助手',
				me: '个人中心AI助手为您服务',
				data: '数据分析AI助手已就绪',
				general: 'AI智能助手为您服务'
			};
			return titles[this.pageType] || titles.general;
		},
		
		// 获取欢迎文本
		getWelcomeText() {
			const texts = {
				index: '我可以帮您解答设备管理、故障处理、报修流程等问题',
				me: '我可以帮您解答账户设置、系统使用、功能介绍等问题',
				data: '我可以帮您分析数据趋势、解读图表信息、提供数据洞察',
				general: '我可以帮您解答各种技术和业务问题'
			};
			return texts[this.pageType] || texts.general;
		},
		
		// 获取快捷问题
		getQuickQuestions() {
			const questions = {
				index: ['如何报修设备？', '设备故障怎么处理？', '维修流程是什么？'],
				me: ['如何修改密码？', '系统功能介绍', '如何退出登录？'],
				data: ['数据异常怎么办？', '如何看懂图表？', '数据趋势分析'],
				general: ['使用帮助', '常见问题', '联系开发者']
			};
			return questions[this.pageType] || questions.general;
		},
		
		// 浮动按钮触摸开始
		handleTouchStart(e) {
			this.isDragging = false;
			this.startY = e.touches[0].clientY;
			this.startX = e.touches[0].clientX;
		},
		
		// 浮动按钮拖拽
		handleTouchMove(e) {
			e.preventDefault();
			e.stopPropagation();
			
			const touch = e.touches[0];
			const deltaY = Math.abs(touch.clientY - this.startY);
			const deltaX = Math.abs(touch.clientX - this.startX);
			
			if (deltaY > 10 || deltaX > 10) {
				this.isDragging = true;
			}
			
			if (this.isDragging) {
				const systemInfo = uni.getSystemInfoSync();
				const windowHeight = systemInfo.windowHeight;
				const windowWidth = systemInfo.windowWidth;
				const buttonSize = 60;
				
				// 计算新位置
				let newTop = touch.clientY - buttonSize / 2;
				let newRight = windowWidth - touch.clientX - buttonSize / 2;
				
				// 边界限制 - 进一步增加上下移动范围
				if (newTop < 20) newTop = 20;  // 进一步减少上边界限制，从50改为20
				if (newTop > windowHeight - buttonSize - 20) newTop = windowHeight - buttonSize - 20;  // 进一步减少下边界限制，从50改为20
				if (newRight < 15) newRight = 15;  // 左右边界保持不变
				if (newRight > windowWidth - buttonSize - 15) newRight = windowWidth - buttonSize - 15;
				
				// 更新本地位置（不触发全局通知，避免拖拽时的性能问题）
				this.floatPosition.top = newTop;
				this.floatPosition.right = newRight;
			}
		},
		
		// 浮动按钮触摸结束
		handleTouchEnd(e) {
			if (this.isDragging) {
				// 使用全局位置管理器保存位置并通知其他页面
				aiFloatManager.safeUpdatePosition(this.floatPosition, true);
			}
			
			setTimeout(() => {
				this.isDragging = false;
			}, 100);
		},
		
		// 显示AI聊天窗口
		showAIChat() {
			if (this.isDragging) return;
			
			// 设置全局聊天状态
			aiFloatManager.setChatWindowState(true, this.pageType);
			
			this.showFloatBtn = false;
			this.chatWindowVisible = true;
			this.$refs.aiChatPopup.open();
			
			// 打开窗口后滚动到最新消息，使用更长延迟确保弹窗完全打开
			setTimeout(() => {
				this.scrollToBottom();
				// 双重保险
				setTimeout(() => {
					this.scrollToBottom();
				}, 200);
			}, 400);
		},
		
		// 关闭AI聊天窗口
		closeAIChat() {
			// 更新全局聊天状态
			aiFloatManager.setChatWindowState(false);
			
			this.showFloatBtn = true;
			this.chatWindowVisible = false;
			this.$refs.aiChatPopup.close();
		},
		
		// 最小化聊天窗口
		minimizeChat() {
			// 更新全局聊天状态
			aiFloatManager.setChatWindowState(false);
			
			this.showFloatBtn = true;
			this.chatWindowVisible = false;
			this.$refs.aiChatPopup.close();
		},

		// 显示清空确认弹窗
		showClearConfirm() {
			if (this.chatHistory.length === 0) {
				uni.showToast({
					icon: 'none',
					title: '暂无聊天记录'
				});
				return;
			}
			this.$refs.clearConfirmPopup.open();
		},

		// 关闭清空确认弹窗
		closeClearConfirm() {
			this.$refs.clearConfirmPopup.close();
		},

		// 清空聊天记录
		clearChatHistory() {
			this.chatHistory = [];
			this.lastFailedMessage = '';
			this.$refs.clearConfirmPopup.close();
			
			uni.showToast({
				icon: 'success',
				title: '聊天记录已清空'
			});
		},
		
		// 发送快捷消息
		sendQuickMessage(question) {
			this.inputMessage = question;
			this.sendMessage();
		},
		
		// 发送消息
		sendMessage() {
			if (!this.inputMessage.trim() || this.isLoading) return;
			
			const userMessage = this.inputMessage.trim();
			const currentTime = this.formatTime(new Date());
			
			// 保存消息用于可能的重试
			this.lastFailedMessage = userMessage;
			
			// 添加用户消息到聊天历史
			this.chatHistory.push({
				type: 'user',
				content: userMessage,
				time: currentTime
			});
			
			// 清空输入框
			this.inputMessage = '';
			
			// 强制滚动到用户消息
			this.$nextTick(() => {
				// 用户消息显示后立即滚动
				setTimeout(() => {
					this.scrollToBottom();
				}, 50);
				
				// 为即将显示的AI思考状态预留滚动
				setTimeout(() => {
					this.scrollToBottom();
				}, 150);
			});
			
			// 调用AI接口
			this.callAIChat(userMessage);
		},
		
		// 调用AI接口
		callAIChat(message) {
			this.isLoading = true;
			
			// AI开始思考时，立即触发多重滚动确保思考状态显示
			// 使用立即执行 + $nextTick + 多重延迟的组合
			this.scrollToBottom(); // 立即滚动
			
			this.$nextTick(() => {
				this.scrollToBottom(); // DOM更新后滚动
				
				// 多重延迟滚动，确保思考状态完全显示
				setTimeout(() => {
					this.scrollToBottom();
				}, 50);
				
				setTimeout(() => {
					this.scrollToBottom();
				}, 150);
				
				setTimeout(() => {
					this.scrollToBottom();
				}, 300);
				
				setTimeout(() => {
					this.scrollToBottom();
				}, 500); // 增加额外的延迟滚动
			});
			
			const userinfo = uni.getStorageSync('userinfo');
			
			// 构建上下文信息
			const context = {
				deviceInfo: {
					workshop: userinfo.WORKSHOP || '未知',
					userRole: userinfo.ROLE || '用户',
					userName: userinfo.USERNAME || '用户'
				},
				pageContext: {
					pageType: this.pageType,
					timestamp: new Date().toISOString()
				}
			};
			
			// 添加超时处理
			const timeoutPromise = new Promise((resolve, reject) => {
				setTimeout(() => {
					reject(new Error('请求超时'));
				}, 30000); // 30秒超时
			});
			
			Promise.race([
				this.$http.request({
					url: '/AIchat/AIchat.php', // AI模型请求后台地址，确保URL正确
					method: 'POST',
					data: {
						TOKEN: userinfo.TOKEN,
						message: message,
						chatHistory: JSON.stringify(this.chatHistory.slice(-10)), // 只发送最近10条
						context: JSON.stringify(context)
					}
				}),
				timeoutPromise
			]).then((res) => {
				this.isLoading = false;
				
				if (res.code === 200) {
					// 清除失败的消息记录
					this.lastFailedMessage = '';
					
					const aiResponse = {
						type: 'ai',
						content: res.data.reply || res.reply || res.data || '抱歉，AI暂时无法回复',
						time: this.formatTime(new Date())
					};
					
					this.chatHistory.push(aiResponse);
					
					// 强制滚动到AI回复
					this.$nextTick(() => {
						// 添加短暂延迟，确保AI回复内容完全渲染
						setTimeout(() => {
							this.scrollToBottom();
						}, 100);
						
						// 额外保险滚动，处理内容较长的情况
						setTimeout(() => {
							this.scrollToBottom();
						}, 300);
					});
				} else {
					console.error('AI接口错误:', res);
					// 添加更友好的错误处理
					this.handleAIError(res);
				}
			}).catch((err) => {
				this.isLoading = false;
				console.error('AI聊天错误:', err);
				this.handleAIError(err);
			});
		},
		
		// 处理AI错误
		handleAIError(error) {
			let errorMessage = 'AI服务暂时不可用';
			let aiResponse = null;
			
			if (error && error.msg) {
				if (error.msg.includes('timeout') || error.msg.includes('超时')) {
					errorMessage = 'AI正在深度思考中，请稍后再试';
					aiResponse = {
						type: 'ai',
						content: '抱歉，我需要更多时间来思考您的问题。由于当前服务负载较高，请您稍后重试，或者尝试询问更简单的问题。',
						time: this.formatTime(new Date())
					};
				} else if (error.code === 500) {
					errorMessage = 'AI服务正在维护中';
					aiResponse = {
						type: 'ai',
						content: '很抱歉，AI服务当前正在维护升级中。您可以：\n1. 稍后重试\n2. 联系系统管理员\n3. 查看系统帮助文档',
						time: this.formatTime(new Date())
					};
				}
			} else if (error && error.message === '请求超时') {
				errorMessage = 'AI响应超时，请重试';
				aiResponse = {
					type: 'ai',
					content: '网络连接超时，请检查网络状态后重试。如果问题持续存在，请联系技术支持。',
					time: this.formatTime(new Date())
				};
			}
			
			// 显示错误提示
			uni.showToast({
				icon: 'none',
				title: errorMessage,
				duration: 3000
			});
			
			// 如果有AI回复内容，添加到聊天记录
			if (aiResponse) {
				this.chatHistory.push(aiResponse);
				// 强制滚动到错误消息
				this.$nextTick(() => {
					setTimeout(() => {
						this.scrollToBottom();
					}, 100);
					setTimeout(() => {
						this.scrollToBottom();
					}, 300);
				});
			}
		},
		
		// 重试上次失败的消息
		retryLastMessage() {
			if (this.lastFailedMessage && !this.isLoading) {
				this.inputMessage = this.lastFailedMessage;
				this.sendMessage();
			}
		},
		
		// 格式化时间
		formatTime(date) {
			const hours = String(date.getHours()).padStart(2, '0');
			const minutes = String(date.getMinutes()).padStart(2, '0');
			return `${hours}:${minutes}`;
		},
		
		// 滚动到底部
		scrollToBottom() {
			// 生成唯一的滚动值，避免重复滚动被忽略
			const scrollValue = 999999 + Date.now() % 10000;
			
			// 立即滚动
			this.scrollTop = scrollValue;
			
			// DOM更新后再次滚动
			this.$nextTick(() => {
				this.scrollTop = scrollValue + 100;
				
				// 使用递归延迟滚动，确保动态内容完全加载
				let attempts = 0;
				const maxAttempts = 8;
				
				const recursiveScroll = () => {
					if (attempts < maxAttempts) {
						attempts++;
						this.scrollTop = 999999 + Date.now() % 10000 + attempts * 50;
						
						// 逐渐增加延迟时间，适应不同的内容加载速度
						const delay = attempts <= 3 ? 50 : attempts <= 5 ? 100 : 200;
						setTimeout(recursiveScroll, delay);
					}
				};
				
				// 开始递归滚动
				setTimeout(recursiveScroll, 50);
			});
		},
		
		// 语音功能切换
		// toggleVoice() {
		// 	uni.showToast({
		// 		icon: 'none',
		// 		title: '语音功能开发中...'
		// 	});
		// },
		
		// 订阅全局位置变化
		subscribeToPositionChanges() {
			this.unsubscribePosition = aiFloatManager.subscribe((newPosition) => {
				// 无感更新位置，避免视觉跳动
				this.floatPosition = { ...newPosition };
			});
		},
		
		// 订阅聊天窗口状态变化
		subscribeToChatStateChanges() {
			this.unsubscribeChatState = aiFloatManager.subscribeChatState((newState, oldState) => {
				// 如果聊天窗口应该关闭，且当前窗口是打开的
				if (!aiFloatManager.shouldShowChatWindow(this.pageType) && this.chatWindowVisible) {
					this.forceCloseChatWindow();
				}
			});
		},
		
		// 强制关闭聊天窗口（不更新全局状态）
		forceCloseChatWindow() {
			this.showFloatBtn = true;
			this.chatWindowVisible = false;
			
			// 检查弹窗是否存在并关闭
			if (this.$refs.aiChatPopup) {
				this.$refs.aiChatPopup.close();
			}
		},
		
		// 获取初始位置（兼容旧版本）
		getInitialPosition() {
			return aiFloatManager.getPosition();
		},
		
		// 手动同步位置（用于特殊情况）
		syncPosition() {
			const currentPosition = aiFloatManager.getPosition();
			this.floatPosition = { ...currentPosition };
		}
	}
};
</script>

<style lang="scss" scoped>
/* AI浮动按钮 */
.ai-float-btn {
	position: fixed;
	width: 60px;
	height: 60px;
	border-radius: 50%;
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	display: flex;
	align-items: center;
	justify-content: center;
	z-index: 9999;
	box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
	/* 移除transition，避免位置同步时的动画效果 */
	/* transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); */
	touch-action: none;
	user-select: none;
	/* 添加transform will-change优化，减少重绘 */
	will-change: transform;
	/* 使用transform3d开启硬件加速 */
	transform: translate3d(0, 0, 0);

	&:active {
		transform: translate3d(0, 0, 0) scale(0.95);
		transition: transform 0.1s ease;
	}

	.ai-icon {
		z-index: 2;
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		
		.cuIcon-service {
			font-size: 20px;
			color: #fff;
			margin-bottom: 2px;
		}
		
		.ai-label {
			font-size: 8px;
			color: #fff;
			font-weight: 600;
			line-height: 1;
			text-align: center;
			opacity: 0.95;
			white-space: nowrap;
		}
	}

	.ai-pulse {
		position: absolute;
		width: 100%;
		height: 100%;
		border-radius: 50%;
		background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
		opacity: 0.6;
		animation: ai-pulse 2s infinite;
	}
}

@keyframes ai-pulse {
	0% {
		transform: scale(1);
		opacity: 0.6;
	}
	50% {
		transform: scale(1.1);
		opacity: 0.3;
	}
	100% {
		transform: scale(1.2);
		opacity: 0;
	}
}

/* AI聊天弹窗 */
.ai-chat-modal {
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 20px;
}

.chat-container {
	width: 90vw;
	max-width: 400px;
	height: 70vh;
	background: #fff;
	border-radius: 24px;
	overflow: hidden;
	box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
	display: flex;
	flex-direction: column;
	position: relative;

	&::before {
		content: '';
		position: absolute;
		top: 0;
		left: 0;
		right: 0;
		height: 1px;
		background: linear-gradient(90deg, transparent, rgba(102, 126, 234, 0.3), transparent);
	}
}

/* 聊天头部 */
.chat-header {
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	padding: 20px;
	display: flex;
	align-items: center;
	justify-content: space-between;
	position: relative;

	.header-left {
		display: flex;
		align-items: center;
		flex: 1;
	}

	.ai-avatar {
		width: 40px;
		height: 40px;
		border-radius: 50%;
		background: rgba(255, 255, 255, 0.2);
		display: flex;
		align-items: center;
		justify-content: center;
		margin-right: 12px;
		position: relative;

		.cuIcon-service {
			font-size: 20px;
			color: #fff;
		}

		.online-indicator {
			position: absolute;
			bottom: 2px;
			right: 2px;
			width: 8px;
			height: 8px;
			background: #52c41a;
			border-radius: 50%;
			border: 2px solid #fff;
		}
	}

	.header-info {
		flex: 1;

		.chat-title {
			display: block;
			font-size: 16px;
			font-weight: 600;
			color: #fff;
			margin-bottom: 2px;
		}

		.chat-subtitle {
			display: block;
			font-size: 12px;
			color: rgba(255, 255, 255, 0.8);
		}
	}

	.header-actions {
		display: flex;
		align-items: center;
		gap: 8px;

		.clear-btn,
		.minimize-btn,
		.close-btn {
			width: 32px;
			height: 32px;
			border-radius: 50%;
			background: rgba(255, 255, 255, 0.2);
			display: flex;
			align-items: center;
			justify-content: center;
			transition: all 0.2s;

			&:active {
				background: rgba(255, 255, 255, 0.3);
				transform: scale(0.95);
			}

			.cuIcon-delete,
			.cuIcon-fold,
			.cuIcon-close {
				font-size: 14px;
				color: #fff;
			}
		}

		.clear-btn {
			&:active {
				background: rgba(255, 87, 87, 0.3);
			}
		}
	}
}

/* 聊天内容区域 */
.chat-content {
	flex: 1;
	overflow: hidden;
	background: #fafafa;

	.message-list {
		height: 100%;
		padding: 8px 20px 8px 20px; /* 减少顶部和底部内边距，避免遮挡 */
		box-sizing: border-box;
	}
}

/* 欢迎消息 */
.welcome-message {
	text-align: center;
	padding: 10px 20px 20px 20px; /* 进一步减少顶部内边距 */

	.welcome-icon {
		position: relative;
		display: inline-block;
		margin-bottom: 20px;

		.icon-bg {
			width: 60px;
			height: 60px;
			border-radius: 50%;
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
			display: flex;
			align-items: center;
			justify-content: center;
			position: relative;
			z-index: 2;

			.cuIcon-service {
				font-size: 24px;
				color: #fff;
			}
		}

		.icon-glow {
			position: absolute;
			top: -10px;
			left: -10px;
			right: -10px;
			bottom: -10px;
			border-radius: 50%;
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
			opacity: 0.2;
			animation: glow-pulse 3s infinite;
		}
	}

	.welcome-title {
		display: block;
		font-size: 18px;
		font-weight: 600;
		color: #333;
		margin-bottom: 8px;
	}

	.welcome-text {
		display: block;
		font-size: 14px;
		color: #666;
		line-height: 1.5;
		margin-bottom: 20px;
	}

	.retry-section {
		margin-bottom: 20px;

		.retry-btn {
			background: linear-gradient(135deg, #fa8c16 0%, #faad14 100%);
			border-radius: 24px;
			padding: 12px 20px;
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 8px;
			transition: all 0.2s;

			&:active {
				transform: scale(0.98);
				background: linear-gradient(135deg, #e6780e 0%, #e09f12 100%);
			}

			.cuIcon-refresh {
				font-size: 16px;
				color: #fff;
			}

			text:last-child {
				font-size: 14px;
				color: #fff;
				font-weight: 500;
			}
		}
	}

	.quick-questions {
		display: flex;
		flex-direction: column;
		gap: 8px;

		.quick-item {
			background: #fff;
			border: 1px solid #e8e8e8;
			border-radius: 20px;
			padding: 10px 16px;
			transition: all 0.2s;

			&:active {
				background: #f0f0f0;
				transform: scale(0.98);
			}

			text {
				font-size: 13px;
				color: #666;
			}
		}
	}
}

@keyframes glow-pulse {
	0%, 100% {
		opacity: 0.2;
		transform: scale(1);
	}
	50% {
		opacity: 0.4;
		transform: scale(1.05);
	}
}

/* 消息项 */
.message-item {
	display: flex;
	margin-bottom: 12px; /* 进一步减少消息间距 */
	align-items: flex-start;
	width: 100%;

	&.user {
		justify-content: flex-end; /* 用户消息整体靠右对齐 */

		.message-content {
			align-items: flex-end;
			margin-right: 12px; /* 内容与头像的间距 */
		}

		.message-bubble {
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
			color: #fff;
		}

		.message-time {
			text-align: right;
		}
	}

	&.ai {
		justify-content: flex-start; /* AI消息整体靠左对齐 */

		.message-content {
			align-items: flex-start;
			margin-left: 12px; /* 内容与头像的间距 */
		}
	}

	.message-avatar {
		width: 36px;
		height: 36px;
		border-radius: 50%;
		background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
		display: flex;
		align-items: center;
		justify-content: center;
		flex-shrink: 0;
		margin-top: 6px;

		&.user-avatar {
			background: linear-gradient(135deg, #52c41a 0%, #73d13d 100%);
		}

		.cuIcon-service,
		.cuIcon-people {
			font-size: 16px;
			color: #fff;
		}
	}

	.message-content {
		display: flex;
		flex-direction: column;
		max-width: 65%; /* 减少最大宽度，为头像留出空间 */
	}

	.message-bubble {
		background: #fff;
		border-radius: 20px;
		padding: 12px 16px;
		box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
		margin-bottom: 4px;
		position: relative;

		.message-text {
			font-size: 14px;
			line-height: 1.4;
			color: #333;
			word-break: break-word;
		}
	}

	.message-time {
		font-size: 11px;
		color: #999;
		padding: 0 8px;
	}
}

/* AI思考状态 */
.thinking-message {
	/* 确保思考状态能够被滚动到 */
	margin-bottom: 12px; /* 与其他消息保持一致的间距 */
	/* 强制显示在底部 */
	min-height: 60px; /* 确保有足够高度触发滚动 */
	
	.thinking-bubble {
		background: #fff;
		border-radius: 20px;
		padding: 16px 20px;
		box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
		display: flex;
		align-items: center;
		gap: 12px;
		/* 添加渐入动画 */
		animation: fadeInUp 0.3s ease-out;
		/* 确保思考气泡完全可见 */
		position: relative;
		z-index: 1;

		.thinking-dots {
			display: flex;
			gap: 4px;

			span {
				width: 6px;
				height: 6px;
				border-radius: 50%;
				background: #667eea;
				animation: thinking-bounce 1.4s infinite ease-in-out;

				&:nth-child(1) { animation-delay: -0.32s; }
				&:nth-child(2) { animation-delay: -0.16s; }
				&:nth-child(3) { animation-delay: 0s; }
			}
		}

		.thinking-text {
			font-size: 13px;
			color: #666;
		}
	}
}

@keyframes thinking-bounce {
	0%, 80%, 100% {
		transform: scale(0.8);
		opacity: 0.5;
	}
	40% {
		transform: scale(1);
		opacity: 1;
	}
}

@keyframes fadeInUp {
	from {
		opacity: 0;
		transform: translateY(10px);
	}
	to {
		opacity: 1;
		transform: translateY(0);
	}
}

/* 输入区域 */
.chat-input {
	background: #fff;
	border-top: 1px solid #f0f0f0;
	padding: 8px 20px 12px 20px; /* 减少内边距，避免底部遮挡 */
	box-sizing: border-box;

	.input-container {
		.input-wrapper {
			display: flex;
			align-items: center;
			background: #f8f8f8;
			border-radius: 24px;
			padding: 4px 4px 4px 16px;
			border: 2px solid transparent;
			transition: all 0.2s;

			&:focus-within {
				border-color: #667eea;
				background: #fff;
			}

			.message-input {
				flex: 1;
				border: none;
				background: transparent;
				font-size: 14px;
				color: #333;
				height: 36px;
				line-height: 36px;

				&::placeholder {
					color: #999;
				}

				&:focus {
					outline: none;
				}
			}

			.input-actions {
				display: flex;
				align-items: center;
				gap: 4px;

				.voice-btn,
				.send-btn {
					width: 36px;
					height: 36px;
					border-radius: 50%;
					display: flex;
					align-items: center;
					justify-content: center;
					transition: all 0.2s;

					.cuIcon-voice,
					.cuIcon-send {
						font-size: 16px;
					}
				}

				.voice-btn {
					background: transparent;

					.cuIcon-voice {
						color: #999;
					}

					&:active {
						background: #f0f0f0;
					}
				}

				.send-btn {
					background: #e8e8e8;

					.cuIcon-send {
						color: #999;
					}

					&.active {
						background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);

						.cuIcon-send {
							color: #fff;
						}
					}

					&:active {
						transform: scale(0.95);
					}
				}
			}
		}
	}
}

/* 深色模式适配 */
@media (prefers-color-scheme: dark) {
	.chat-container {
		background: #1a1a1a;
	}

	.chat-content {
		background: #2a2a2a;
	}

	.welcome-message {
		.welcome-title {
			color: #fff;
		}

		.welcome-text {
			color: #ccc;
		}

		.quick-questions .quick-item {
			background: #333;
			border-color: #444;

			text {
				color: #ccc;
			}
		}
	}

	.message-item .message-bubble {
		background: #333;

		.message-text {
			color: #fff;
		}
	}

	.thinking-message .thinking-bubble {
		background: #333;

		.thinking-text {
			color: #ccc;
		}
	}

	.chat-input {
		background: #1a1a1a;
		border-top-color: #333;

		.input-wrapper {
			background: #333;

			&:focus-within {
				background: #444;
			}

			.message-input {
				color: #fff;

				&::placeholder {
					color: #999;
				}
			}
		}
	}
}

/* 清空确认弹窗 */
.clear-confirm-modal {
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 20px;
}

.confirm-content {
	width: 80vw;
	max-width: 300px;
	background: #fff;
	border-radius: 20px;
	padding: 30px 20px 20px 20px;
	display: flex;
	flex-direction: column;
	align-items: center;
	box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);

	.confirm-icon {
		width: 50px;
		height: 50px;
		border-radius: 50%;
		background: rgba(255, 87, 87, 0.1);
		display: flex;
		align-items: center;
		justify-content: center;
		margin-bottom: 20px;

		.cuIcon-warn {
			font-size: 24px;
			color: #ff5757;
		}
	}

	.confirm-title {
		font-size: 18px;
		font-weight: 600;
		color: #333;
		margin-bottom: 10px;
	}

	.confirm-message {
		font-size: 14px;
		color: #666;
		text-align: center;
		line-height: 1.5;
		margin-bottom: 25px;
	}

	.confirm-actions {
		display: flex;
		width: 100%;
		gap: 12px;

		.cancel-btn,
		.confirm-btn {
			flex: 1;
			height: 44px;
			border-radius: 12px;
			display: flex;
			align-items: center;
			justify-content: center;
			font-size: 16px;
			font-weight: 500;
			transition: all 0.2s;

			&:active {
				transform: scale(0.98);
			}
		}

		.cancel-btn {
			background: #f5f5f5;
			color: #666;

			&:active {
				background: #e8e8e8;
			}
		}

		.confirm-btn {
			background: linear-gradient(135deg, #ff5757 0%, #ff7b7b 100%);
			color: #fff;

			&:active {
				background: linear-gradient(135deg, #e64545 0%, #e66969 100%);
			}
		}
	}
}

/* 深色模式下的确认弹窗 */
@media (prefers-color-scheme: dark) {
	.confirm-content {
		background: #2a2a2a;

		.confirm-title {
			color: #fff;
		}

		.confirm-message {
			color: #ccc;
		}

		.cancel-btn {
			background: #404040;
			color: #ccc;

			&:active {
				background: #333;
			}
		}
	}
}
</style>
