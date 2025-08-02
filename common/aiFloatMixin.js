/**
 * AI浮动按钮全局混入
 * 为页面提供AI浮动按钮位置同步功能
 */
import aiFloatManager from '@/common/aiFloatManager.js';

export default {
    data() {
        return {
            aiFloatPosition: { top: 300, right: 15 },
            aiPositionUnsubscribe: null
        };
    },
    
    mounted() {
        // 自动订阅位置变化
        this.initAIFloatPosition();
    },
    
    beforeDestroy() {
        // 清理订阅
        this.cleanupAIFloatPosition();
    },
    
    methods: {
        /**
         * 初始化AI浮动按钮位置同步
         */
        initAIFloatPosition() {
            // 订阅位置变化
            this.aiPositionUnsubscribe = aiFloatManager.subscribe((newPosition) => {
                this.aiFloatPosition = { ...newPosition };
            });
        },
        
        /**
         * 清理AI浮动按钮位置订阅
         */
        cleanupAIFloatPosition() {
            if (this.aiPositionUnsubscribe) {
                this.aiPositionUnsubscribe();
                this.aiPositionUnsubscribe = null;
            }
        },
        
        /**
         * 手动更新AI浮动按钮位置
         * @param {Object} newPosition - 新位置 {top, right}
         */
        updateAIFloatPosition(newPosition) {
            aiFloatManager.safeUpdatePosition(newPosition, true);
        },
        
        /**
         * 重置AI浮动按钮位置
         */
        resetAIFloatPosition() {
            aiFloatManager.resetPosition();
        },
        
        /**
         * 获取当前AI浮动按钮位置
         * @returns {Object} 位置对象 {top, right}
         */
        getAIFloatPosition() {
            return aiFloatManager.getPosition();
        }
    }
};
