/**
 * AI浮动按钮全局位置管理器
 * 确保所有页面的AI浮动按钮位置同步，实现无感刷新
 * 新增：聊天窗口状态管理，实现跨页面自动关闭
 */
class AIFloatManager {
    constructor() {
        this.position = { top: 300, right: 15 }; // 默认位置
        this.subscribers = new Set(); // 订阅者集合
        this.isInitialized = false;
        
        // 新增：聊天窗口状态管理
        this.chatWindowState = {
            isOpen: false,           // 是否打开聊天窗口
            activePageType: '',      // 当前活跃的页面类型
            openedByPageType: ''     // 打开聊天窗口的页面类型
        };
        this.chatStateSubscribers = new Set(); // 聊天状态订阅者
        
        // 初始化位置
        this.loadPosition();
    }
    
    /**
     * 从本地存储加载位置
     */
    loadPosition() {
        try {
            const savedPosition = uni.getStorageSync('ai_float_position');
            if (savedPosition && typeof savedPosition === 'object') {
                this.position = { ...this.position, ...savedPosition };
            }
            this.isInitialized = true;
        } catch (error) {
            console.warn('加载AI浮动按钮位置失败:', error);
            this.isInitialized = true;
        }
    }
    
    /**
     * 保存位置到本地存储
     */
    savePosition() {
        try {
            uni.setStorageSync('ai_float_position', this.position);
        } catch (error) {
            console.warn('保存AI浮动按钮位置失败:', error);
        }
    }
    
    /**
     * 获取当前位置
     */
    getPosition() {
        return { ...this.position };
    }
    
    /**
     * 更新位置并通知所有订阅者
     * @param {Object} newPosition - 新位置 {top, right}
     * @param {Boolean} save - 是否保存到本地存储
     */
    updatePosition(newPosition, save = true) {
        // 更新位置
        this.position = { ...this.position, ...newPosition };
        
        // 保存到本地存储
        if (save) {
            this.savePosition();
        }
        
        // 通知所有订阅者
        this.notifySubscribers();
    }
    
    /**
     * 订阅位置变化
     * @param {Function} callback - 位置变化回调函数
     * @returns {Function} 取消订阅函数
     */
    subscribe(callback) {
        this.subscribers.add(callback);
        
        // 如果已初始化，立即调用回调
        if (this.isInitialized) {
            callback(this.getPosition());
        }
        
        // 返回取消订阅函数
        return () => {
            this.subscribers.delete(callback);
        };
    }
    
    /**
     * 通知所有订阅者位置变化
     */
    notifySubscribers() {
        const position = this.getPosition();
        this.subscribers.forEach(callback => {
            try {
                callback(position);
            } catch (error) {
                console.warn('AI浮动按钮位置回调执行失败:', error);
            }
        });
    }
    
    /**
     * 验证位置合法性
     * @param {Object} position - 位置对象
     * @returns {Object} 修正后的位置
     */
    validatePosition(position) {
        const systemInfo = uni.getSystemInfoSync();
        const windowHeight = systemInfo.windowHeight;
        const windowWidth = systemInfo.windowWidth;
        const buttonSize = 60;
        
        let { top, right } = position;
        
        // 边界限制
        if (top < 100) top = 100;
        if (top > windowHeight - buttonSize - 100) top = windowHeight - buttonSize - 100;
        if (right < 15) right = 15;
        if (right > windowWidth - buttonSize - 15) right = windowWidth - buttonSize - 15;
        
        return { top, right };
    }
    
    /**
     * 安全更新位置（带验证）
     * @param {Object} newPosition - 新位置
     * @param {Boolean} save - 是否保存
     */
    safeUpdatePosition(newPosition, save = true) {
        const validatedPosition = this.validatePosition(newPosition);
        this.updatePosition(validatedPosition, save);
    }
    
    /**
     * 重置位置为默认值
     */
    resetPosition() {
        const defaultPosition = { top: 300, right: 15 };
        this.updatePosition(defaultPosition, true);
    }
    
    /**
     * 清理所有订阅者
     */
    cleanup() {
        this.subscribers.clear();
        this.chatStateSubscribers.clear();
    }
    
    // ==================== 聊天窗口状态管理 ====================
    
    /**
     * 设置聊天窗口状态
     * @param {Boolean} isOpen - 是否打开
     * @param {String} pageType - 页面类型
     */
    setChatWindowState(isOpen, pageType = '') {
        const oldState = { ...this.chatWindowState };
        
        this.chatWindowState.isOpen = isOpen;
        
        if (isOpen) {
            this.chatWindowState.openedByPageType = pageType;
        } else {
            // 关闭时清空打开页面记录
            this.chatWindowState.openedByPageType = '';
        }
        
        // 通知所有聊天状态订阅者
        this.notifyChatStateSubscribers(oldState);
    }
    
    /**
     * 设置当前活跃页面
     * @param {String} pageType - 页面类型
     */
    setActivePageType(pageType) {
        const oldActivePageType = this.chatWindowState.activePageType;
        this.chatWindowState.activePageType = pageType;
        
        // 如果聊天窗口是打开的，并且切换到了不同的页面，自动关闭聊天窗口
        if (this.chatWindowState.isOpen && 
            this.chatWindowState.openedByPageType !== pageType && 
            this.chatWindowState.openedByPageType !== '') {
            
            //console.log(`AI聊天窗口自动关闭: 从 ${this.chatWindowState.openedByPageType} 切换到 ${pageType}`);
            this.setChatWindowState(false);
        }
    }
    
    /**
     * 获取聊天窗口状态
     * @returns {Object} 聊天窗口状态
     */
    getChatWindowState() {
        return { ...this.chatWindowState };
    }
    
    /**
     * 判断当前页面是否应该显示聊天窗口
     * @param {String} pageType - 页面类型
     * @returns {Boolean} 是否应该显示
     */
    shouldShowChatWindow(pageType) {
        return this.chatWindowState.isOpen && 
               this.chatWindowState.openedByPageType === pageType;
    }
    
    /**
     * 订阅聊天状态变化
     * @param {Function} callback - 状态变化回调函数
     * @returns {Function} 取消订阅函数
     */
    subscribeChatState(callback) {
        this.chatStateSubscribers.add(callback);
        
        // 立即调用回调，提供当前状态
        callback(this.getChatWindowState());
        
        // 返回取消订阅函数
        return () => {
            this.chatStateSubscribers.delete(callback);
        };
    }
    
    /**
     * 通知所有聊天状态订阅者
     * @param {Object} oldState - 旧状态
     */
    notifyChatStateSubscribers(oldState) {
        const newState = this.getChatWindowState();
        this.chatStateSubscribers.forEach(callback => {
            try {
                callback(newState, oldState);
            } catch (error) {
                console.warn('AI聊天状态回调执行失败:', error);
            }
        });
    }
    
    /**
     * 强制关闭所有页面的聊天窗口
     */
    forceCloseAllChatWindows() {
        if (this.chatWindowState.isOpen) {
            console.log('强制关闭所有AI聊天窗口');
            this.setChatWindowState(false);
        }
    }
}

// 创建全局单例
const aiFloatManager = new AIFloatManager();

export default aiFloatManager;
