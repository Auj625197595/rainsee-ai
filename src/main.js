import Vue from 'vue'
import App from './App.vue'
import { store } from './store'

Vue.config.productionTip = false
Vue.prototype.$store = { state: store } // Mock Vuex structure for compatibility

new Vue({
  render: h => h(App),
}).$mount('#app')
