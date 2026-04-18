import '@/assets/styles/main.css'
import { createApp } from 'vue'
// Import Plugins
import { pinia } from '@/plugins/pinia'
import { vuetify } from '@/plugins/vuetify'
import { i18n } from '@/plugins/i18n'
import '@/plugins/vee-validate'
import '@/plugins/ag-charts'
// Import Routes
import { router } from '@/routes'

import App from '@/App.vue'
import casl from '@/plugins/casl'

const app = createApp(App)

app.use(casl)
app.use(pinia)
app.use(vuetify)
app.use(router)
app.use(i18n)

app.mount('#app')
