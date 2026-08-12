import { maskAmountInput } from '@core/utils/formatters'

/**
 * Formatea montos en vivo mientras se escribe (es: 1.234,56).
 *
 * Uso (input nativo):
 *   <input v-currency-live type="text" :value="..." @blur="..." />
 *
 * Uso (Vuetify):
 *   <VTextField v-currency-live ... />
 */
function resolveInput(el) {
  if (!el)
    return null

  if (el.tagName === 'INPUT')
    return el

  return el.querySelector?.('input')
}

function bindCurrencyLive(el) {
  const input = resolveInput(el)
  if (!input || input.__currencyLiveBound)
    return

  const onInput = () => {
    const previous = input.value
    const formatted = maskAmountInput(previous)

    if (formatted === previous)
      return

    const posFromEnd = previous.length - (input.selectionEnd ?? previous.length)

    input.value = formatted

    const newPos = Math.max(0, formatted.length - posFromEnd)

    try {
      input.setSelectionRange(newPos, newPos)
    }
    catch {
      // ignore si el input no soporta selection
    }

    // sincroniza v-model / Vuetify
    input.dispatchEvent(new Event('input', { bubbles: true }))
  }

  input.addEventListener('input', onInput)
  input.__currencyLiveBound = true
  el.__currencyLive = { input, onInput }
}

function unbindCurrencyLive(el) {
  const state = el.__currencyLive
  if (!state)
    return

  state.input.removeEventListener('input', state.onInput)
  delete state.input.__currencyLiveBound
  delete el.__currencyLive
}

export const currencyLive = {
  mounted: bindCurrencyLive,
  // Vuetify monta el <input> un tick después
  updated(el) {
    if (!el.__currencyLive)
      bindCurrencyLive(el)
  },
  unmounted: unbindCurrencyLive,
}

export default currencyLive
