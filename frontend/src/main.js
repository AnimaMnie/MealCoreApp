import { createApp } from 'vue'
import '@fortawesome/fontawesome-free/css/all.min.css'
import './assets/app.css'
import './assets/login.css'
import './assets/extras.css'
import App from './App.vue'
import router from './router'

createApp(App).use(router).mount('#app')
