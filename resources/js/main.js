import App from '@/App.vue'
import { formatAmount, formatAmountValue, formatDate, parseAmount } from '@core/utils/formatters'
import { registerPlugins } from '@core/utils/plugins'
import { createApp } from 'vue'
import Toast, { useToast } from 'vue-toastification'
import 'vue-toastification/dist/index.css'

// Styles
import '@core-scss/template/index.scss'
import '@layouts/styles/index.scss'

// Create vue app
const app = createApp(App)

app.config.globalProperties.$formatDate = formatDate
app.config.globalProperties.$formatAmount = formatAmount
app.config.globalProperties.$formatAmountValue = formatAmountValue
app.config.globalProperties.$parseAmount = parseAmount
app.use(Toast)
app.config.globalProperties.$toast = useToast()

// Register plugins
registerPlugins(app)

// Mount vue app
app.mount('#app')
