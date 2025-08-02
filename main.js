import App from './App'


// #ifndef VUE3
import Vue from 'vue'
import './uni.promisify.adaptor'
import http from '@/common/request.js'
import cuCustom from './colorui/components/cu-custom.vue'
import aiFloatManager from '@/common/aiFloatManager.js'

//AI浮动按钮位置管理器
Vue.prototype.$aiFloatManager = aiFloatManager

Vue.component('cu-custom',cuCustom)
Vue.config.productionTip = false
App.mpType = 'app'
const app = new Vue({
  ...App
})
app.$mount()
// #endif

// #ifdef VUE3
import { createSSRApp } from 'vue'
export function createApp() {
  const app = createSSRApp(App)
  return {
    app
  }
}
// #endif