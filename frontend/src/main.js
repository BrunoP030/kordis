import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { registerLicense } from '@syncfusion/ej2-base'
import App from './App.vue'
import router from './router'
import './style.css'

// Licença Syncfusion (community ou comercial):
// https://www.syncfusion.com/account/manage-trials/downloads
registerLicense(import.meta.env.VITE_SYNCFUSION_LICENSE_KEY ?? '')

const app = createApp(App)
app.use(createPinia())
app.use(router)
app.mount('#app')
