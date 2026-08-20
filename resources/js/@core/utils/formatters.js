// TODO: Try to implement this: https://twitter.com/fireship_dev/status/1565424801216311297
export const kFormatter = num => {
  const regex = /\B(?=(\d{3})+(?!\d))/g

  return Math.abs(num) > 9999 ? `${Math.sign(num) * +((Math.abs(num) / 1000).toFixed(1))}k` : Math.abs(num).toFixed(0).replace(regex, ',')
}

/**
 * Fecha en calendario local como YYYY-MM-DD (adecuado para inputs tipo date y APIs).
 * Evita el desfase de día que suele dar solo `toISOString().split('T')[0]` (UTC).
 *
 * @param {Date|string|number|null|undefined} value
 * @returns {string|null}
 */
export const formatDate = value => {
  if (value == null || value === '')
    return null

  const d = value instanceof Date ? value : new Date(value)

  if (Number.isNaN(d.getTime()))
    return null

  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')

  return `${y}-${m}-${day}`
}

/**
 * Monto en formato CR/ES: miles con punto, decimales con coma (siempre agrupa desde 1.000).
 * Ej: 2000 → "2.000,00" · 10000 → "10.000,00"
 *
 * @param {number} n
 * @returns {string}
 */
const formatEsNumber = n => {
  const sign = n < 0 ? '-' : ''
  const [intPart, dec] = Math.abs(n).toFixed(2).split('.')
  const withDots = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.')

  return `${sign}${withDots},${dec}`
}

/**
 * Monto para UI: separadores en español y 2 decimales; inválidos → '—'.
 *
 * @param {string|number|null|undefined} value
 * @returns {string}
 */
export const formatAmount = value => {
  if (value == null || value === '')
    return '—'

  const n = typeof value === 'number' ? value : parseAmount(value)

  if (n === '' || Number.isNaN(n))
    return '—'

  return formatEsNumber(n)
}

/**
 * Convierte monto a número.
 * - UI es: "2.000" / "2.000,50" / "10.000,00"
 * - API/JSON: "2000.00" / 2000 (punto = decimal, no miles)
 *
 * @param {string|number|null|undefined} value
 * @returns {number|''}
 */
export const parseAmount = value => {
  if (value === '' || value == null)
    return ''

  if (typeof value === 'number')
    return Number.isNaN(value) ? '' : value

  let s = String(value).trim().replace(/[^\d.,-]/g, '')

  if (!s || s === '-' || s === '.' || s === ',')
    return ''

  if (s.includes(',')) {
    // Formato CR/ES: miles con punto, decimal con coma
    s = s.replace(/\./g, '').replace(',', '.')
  }
  else {
    const dots = s.match(/\./g) || []

    if (dots.length > 1) {
      // Solo miles: 1.234.567
      s = s.replace(/\./g, '')
    }
    else if (dots.length === 1) {
      const decPart = s.split('.')[1] ?? ''

      // "2000.00" / "2000.5" → decimal API; "2.000" → miles UI
      if (decPart.length !== 3)
        s = s // dejar el punto decimal
      else
        s = s.replace(/\./g, '')
    }
  }

  const n = Number(s)

  return Number.isNaN(n) ? '' : n
}

/**
 * Formatea mientras se escribe: miles con punto y hasta 2 decimales con coma.
 * Ej: "1234567,5" → "1.234.567,5"
 *
 * @param {string} raw
 * @returns {string}
 */
export const maskAmountInput = raw => {
  if (raw == null || raw === '')
    return ''

  let s = String(raw).replace(/[^\d,]/g, '')

  const commaIdx = s.indexOf(',')
  if (commaIdx !== -1) {
    const intPart = s.slice(0, commaIdx).replace(/,/g, '')
    const decPart = s.slice(commaIdx + 1).replace(/,/g, '').slice(0, 2)

    s = `${intPart},${decPart}`
  }

  const [intRaw, dec] = s.split(',')
  const digits = intRaw.replace(/\D/g, '')
  const withDots = digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.')

  return dec !== undefined ? `${withDots},${dec}` : withDots
}

/**
 * Monto para inputs (2 decimales); vacío → ''.
 *
 * @param {string|number|null|undefined} value
 * @returns {string}
 */
export const formatAmountValue = value => {
  if (value === '' || value == null)
    return ''

  const n = typeof value === 'number' ? value : parseAmount(value)

  if (n === '' || Number.isNaN(n))
    return ''

  return formatEsNumber(n)
}
