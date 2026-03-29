import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { Quasar, Notify, Dialog, Loading, copyToClipboard } from 'quasar'
import router from './router'
import App from './App.vue'

// Quasar styles
import '@quasar/extras/roboto-font/roboto-font.css'
import '@quasar/extras/material-icons/material-icons.css'
import '@quasar/extras/material-icons-outlined/material-icons-outlined.css'
import 'quasar/dist/quasar.css'
import './styles/app.scss'

const app = createApp(App)

app.use(createPinia())
app.use(router)
app.use(Quasar, {
  plugins: {
    Notify,
    Dialog,
    Loading,
  },
  config: {
    dark: 'auto',
    notify: {
      position: 'top-right',
      timeout: 3000,
    },
  },
})

// Mount to WordPress or standalone
const mountEl = document.getElementById('tslm-app')
if (mountEl) {
  app.mount(mountEl)
}
