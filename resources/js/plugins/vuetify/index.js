import { createVuetify } from 'vuetify'
import { VBtn } from 'vuetify/components/VBtn'
import { VDateInput } from 'vuetify/labs/VDateInput'
import { VNumberInput } from 'vuetify/labs/VNumberInput'
import defaults from './defaults'
import { icons } from './icons'
import { getStoredTheme, setStoredTheme, themes } from './theme'

// Styles
import '@core-scss/template/libs/vuetify/index.scss'
import 'vuetify/styles'

export default function (app) {
  setStoredTheme(getStoredTheme())

  const vuetify = createVuetify({
    components: {
      VDateInput,
      VNumberInput,
    },
    aliases: {
      IconBtn: VBtn,
    },
    defaults,
    icons,
    theme: {
      defaultTheme: getStoredTheme(),
      themes,
    },
  })

  app.use(vuetify)
}
