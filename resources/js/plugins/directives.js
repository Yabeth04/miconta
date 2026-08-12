import { currencyLive } from '@/directives/currencyLive'

/**
 * Registra directivas globales reutilizables.
 */
export default function (app) {
  app.directive('currency-live', currencyLive)
}
