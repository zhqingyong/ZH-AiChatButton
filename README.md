# AI浮动按钮聊天窗口组件

一个功能完整的AI智能助手浮动按钮组件，支持跨页面位置同步、聊天窗口状态管理，适用于uni-app项目。

## 安装

下载zip包，导入到项目按照说明一步步配置即可，需要移植组件直接复制文件到项目按照结构说明配置即可。

## 功能特性

✅ **智能浮动按钮**
- 可拖拽调整任意位置，无自动吸附
- 跨页面位置同步
- 自动边界检测
- 位置持久化存储

✅ **聊天窗口管理**
- 现代化UI设计
- 跨页面状态同步
- 切换页面自动关闭机制
- 页面切换智能管理

✅ **AI对话功能**
- 支持连续对话
- 消息历史记录
- 快捷问题建议
- 错误重试机制

✅ **响应式设计**
- 深色模式适配
- 多设备兼容
- 流畅动画效果

## 项目参考结构

```
ZH-AiChatButton/
├── components/
│   └── ZH-AiChatButton/
│       └── ZH-AiChatButton.vue          # AI聊天组件主文件
├── common/
│   ├── aiFloatManager.js        # 位置管理器(核心)
│   └── aiFloatMixin.js          # Vue混入(可选)
├── pages/
│   └── index/
│       ├── index.vue            # 示例页面
│       ├── data.vue             # 示例页面
│       └── me.vue               # 示例页面
└── main.js                      # 应用入口
```
### 必需的依赖

#### uni-popup 组件

项目使用了 uni-ui 的 uni-popup 弹窗组件来实现聊天窗口的显示，请先安装uni-ui。

#### ColorUI 图标库

项目使用了 ColorUI 的图标库来显示各种图标，请先配置好colorUI以保障组件图标正常显示，当然也可根据需求自己替换其他图标库。


## 快速开始

### 1. 安装配置

#### 方法一：直接使用aiFloatManager（推荐此方法）

在 `main.js` 中全局注册：

```javascript
import Vue from 'vue'
import aiFloatManager from '@/common/aiFloatManager.js'

// 挂载到Vue原型，全局可用
Vue.prototype.$aiFloatManager = aiFloatManager
```

#### 方法二：使用aiFloatMixin（可选）

```javascript
// 在页面中导入并使用
import aiFloatMixin from '@/common/aiFloatMixin.js'

export default {
    mixins: [aiFloatMixin],
    // 自动获得位置同步功能
}
```

### 2. 在页面中使用

```vue
<template>
    <view class="page">
        <!-- 你的页面内容 -->
        
        <!-- AI聊天组件 -->
        <ai-chat :pageType="'index'"></ai-chat>
    </view>
</template>

<script>
import AiChat from '@/components/ZH-AiChatButton/ZH-AiChatButton.vue'

export default {
    components: {
        AiChat
    },
    
    onShow() {
        // 通知管理器当前页面状态（重要！）
        if (this.$aiFloatManager) {
            this.$aiFloatManager.setActivePageType('index');
        }
    }
}
</script>
```

## 核心组件详解

### aiFloatManager.js - 位置管理器

核心管理器，提供位置同步和聊天状态管理功能。

#### 主要功能

```javascript
// 获取管理器实例（已在main.js中全局注册）
const manager = this.$aiFloatManager;

// 位置管理
manager.getPosition()                    // 获取当前位置
manager.updatePosition({top: 100, right: 20})  // 更新位置
manager.safeUpdatePosition(position)     // 安全更新（带边界检测）
manager.resetPosition()                  // 重置到默认位置

// 位置订阅
const unsubscribe = manager.subscribe((newPosition) => {
    console.log('位置变化:', newPosition);
});

// 聊天状态管理
manager.setChatWindowState(true, 'index')  // 设置聊天窗口状态
manager.setActivePageType('index')         // 设置活跃页面
manager.getChatWindowState()               // 获取聊天状态
manager.shouldShowChatWindow('index')      // 判断是否显示聊天窗口
```

#### 自动功能

- **位置持久化**：自动保存到本地存储
- **边界检测**：防止按钮超出屏幕范围
- **跨页面同步**：所有页面位置实时同步
- **智能关闭**：页面切换时自动关闭聊天窗口

### aiFloatMixin.js - Vue混入（可选）

为Vue组件提供便捷的位置管理功能。

#### 提供的数据和方法

```javascript
// 自动提供的响应式数据
this.aiFloatPosition          // 当前位置 {top, right}

// 自动提供的方法
this.updateAIFloatPosition(pos)  // 更新位置
this.resetAIFloatPosition()      // 重置位置
this.getAIFloatPosition()        // 获取位置

// 自动管理的生命周期
// mounted: 自动订阅位置变化
// beforeDestroy: 自动取消订阅
```

#### 使用示例

```javascript
export default {
    mixins: [aiFloatMixin],
    
    mounted() {
        // 位置数据已自动同步到 this.aiFloatPosition
        console.log('当前位置:', this.aiFloatPosition);
    },
    
    methods: {
        handlePositionChange() {
            // 更新位置
            this.updateAIFloatPosition({top: 200, right: 30});
        }
    }
}
```

### ZH-AiChatButton.vue - AI聊天组件

完整的AI聊天组件，包含浮动按钮和聊天窗口。

#### 组件属性

```javascript
// props
pageType: {
    type: String,
    default: 'general'    // 页面类型: general, index, me, data
}
```

#### 主要功能

- **浮动按钮**：可拖拽、可点击、脉冲动画
- **聊天窗口**：现代化UI、消息历史、快捷问题
- **位置同步**：自动同步管理器位置
- **状态管理**：自动处理聊天窗口状态

#### 内置方法

```javascript
// 聊天相关
this.showAIChat()           // 显示聊天窗口
this.closeAIChat()          // 关闭聊天窗口
this.sendMessage()          // 发送消息
this.clearChatHistory()     // 清空聊天记录

// 位置相关
this.handleTouchMove()      // 处理拖拽
this.syncPosition()         // 手动同步位置
```

## 使用场景和最佳实践

### 场景1：简单使用（推荐）

适合大多数项目，使用aiFloatManager即可：

```javascript
// main.js
Vue.prototype.$aiFloatManager = aiFloatManager

// 页面中
export default {
    components: { AiChat },
    onShow() {
        this.$aiFloatManager.setActivePageType(this.pageType);
    }
}
```

### 场景2：复杂位置管理

需要频繁操作位置时，使用aiFloatMixin：

```javascript
export default {
    mixins: [aiFloatMixin],
    components: { AiChat },
    
    template: `
        <ai-chat 
            :position="aiFloatPosition"
            @position-change="updateAIFloatPosition"
        />
    `
}
```

### 场景3：自定义页面类型

根据页面功能定制AI助手：

```javascript
// 不同页面类型有不同的快捷问题和欢迎语
<ai-chat :pageType="'index'"></ai-chat>     // 首页
<ai-chat :pageType="'me'"></ai-chat>        // 个人中心
<ai-chat :pageType="'data'"></ai-chat>      // 数据页面
```

## 配置选项

### 默认位置设置

```javascript
// 在aiFloatManager.js中修改
this.position = { top: 300, right: 15 };  // 默认位置
```

### 边界限制

```javascript
// 在validatePosition方法中调整
if (top < 100) top = 100;                           // 最小顶部距离
if (top > windowHeight - buttonSize - 100) top = ...;  // 最大顶部距离
if (right < 15) right = 15;                         // 最小右侧距离
```

### 页面类型配置

在ai-chat.vue中自定义不同页面的欢迎语和快捷问题：

```javascript
const titles = {
    index: 'AI智能助手',
    me: '个人助手',
    data: '数据分析助手',
    general: 'AI助手'
};

const questions = {
    index: ['如何使用？', '有什么功能？'],
    me: ['个人设置', '账户信息'],
    data: ['数据分析', '报表生成'],
    general: ['需要帮助吗？']
};
```

## 生命周期管理

### 自动管理（推荐）

使用aiFloatMixin时，生命周期自动管理：

```javascript
export default {
    mixins: [aiFloatMixin],
    // mounted: 自动订阅
    // beforeDestroy: 自动取消订阅
}
```

### 手动管理

直接使用aiFloatManager时：

```javascript
export default {
    data() {
        return {
            unsubscribePosition: null
        }
    },
    
    mounted() {
        // 手动订阅
        this.unsubscribePosition = this.$aiFloatManager.subscribe((pos) => {
            // 处理位置变化
        });
    },
    
    beforeDestroy() {
        // 手动取消订阅
        if (this.unsubscribePosition) {
            this.unsubscribePosition();
        }
    }
}
```

## 样式自定义

### 浮动按钮样式

```scss
.ai-float-btn {
    width: 60px;                    // 按钮大小
    height: 60px;
    border-radius: 50%;             // 圆形按钮
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);  // 渐变背景
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);               // 阴影效果
}
```

### 聊天窗口样式

```scss
.chat-container {
    width: 90vw;                    // 窗口宽度
    max-width: 400px;               // 最大宽度
    height: 70vh;                   // 窗口高度
    border-radius: 24px;            // 圆角
    background: #fff;               // 背景色
}
```

### 深色模式

```scss
@media (prefers-color-scheme: dark) {
    .chat-container {
        background: #1a1a1a;       // 深色背景
    }
    
    .message-bubble {
        background: #333;           // 深色消息气泡
        color: #fff;               // 白色文字
    }
}
```

## 常见问题

### Q: 按钮位置不同步？

A: 确保在页面的onShow中调用：
```javascript
onShow() {
    this.$aiFloatManager.setActivePageType('yourPageType');
}
```

### Q: 聊天窗口不自动关闭？

A: 检查页面类型设置是否正确，确保每个页面都有唯一的pageType。

### Q: 位置超出屏幕？

A: 组件内置边界检测，如需调整边界，修改`validatePosition`方法。

### Q: 如何禁用某个页面的AI按钮？

A: 不在该页面中引入`<ai-chat>`组件即可。

### Q: 如何自定义AI回复？

A: 修改ZH-AiChatButton.vue中的`callAIChat`方法，接入你的AI服务。

## 更新日志

### v1.0.0
- ✅ 基础浮动按钮功能
- ✅ 位置拖拽和同步
- ✅ 聊天窗口UI
- ✅ 跨页面状态管理
- ✅ 位置持久化存储
- ✅ 深色模式支持

