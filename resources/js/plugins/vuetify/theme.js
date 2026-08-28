export const staticPrimaryColor = '#8C57FF'
export const staticPrimaryDarkenColor = '#7E4EE6'

export const THEME_STORAGE_KEY = 'miconta-theme'
export const LOADER_BG_STORAGE_KEY = 'miconta-initial-loader-bg'
export const LOADER_COLOR_STORAGE_KEY = 'miconta-initial-loader-color'

const VALID_THEMES = ['light', 'dark']

const LOADER_COLORS = {
  light: {
    bg: '#FFFFFF',
    color: staticPrimaryColor,
  },
  dark: {
    bg: '#111827',
    color: staticPrimaryColor,
  },
}

export function getLoaderColors(themeName = getStoredTheme()) {
  return LOADER_COLORS[themeName] || LOADER_COLORS.light
}

export function getStoredTheme() {
  if (typeof window === 'undefined')
    return 'light'

  const stored = localStorage.getItem(THEME_STORAGE_KEY)

  return VALID_THEMES.includes(stored) ? stored : 'light'
}

export function setStoredTheme(name) {
  if (typeof window === 'undefined' || !VALID_THEMES.includes(name))
    return

  const loader = getLoaderColors(name)

  localStorage.setItem(THEME_STORAGE_KEY, name)
  localStorage.setItem(LOADER_BG_STORAGE_KEY, loader.bg)
  localStorage.setItem(LOADER_COLOR_STORAGE_KEY, loader.color)
}

export const themes = {
  light: {
    dark: false,
    colors: {
      'primary': staticPrimaryColor,
      'on-primary': '#fff',
      'primary-darken-1': '#7E4EE6',
      'secondary': '#8A8D93',
      'secondary-darken-1': '#7C7F84',
      'on-secondary': '#fff',
      'success': '#56CA00',
      'success-darken-1': '#4DB600',
      'on-success': '#fff',
      'info': '#16B1FF',
      'info-darken-1': '#149FE6',
      'on-info': '#fff',
      'warning': '#FFB400',
      'warning-darken-1': '#E6A200',
      'on-warning': '#fff',
      'error': '#FF4C51',
      'error-darken-1': '#E64449',
      'on-error': '#fff',
      'background': '#f4f5fa',
      'on-background': '#2E263D',
      'surface': '#fff',
      'on-surface': '#2E263D',
      'grey-50': '#FAFAFA',
      'grey-100': '#F5F5F5',
      'grey-200': '#EEEEEE',
      'grey-300': '#E0E0E0',
      'grey-400': '#BDBDBD',
      'grey-500': '#9E9E9E',
      'grey-600': '#757575',
      'grey-700': '#616161',
      'grey-800': '#424242',
      'grey-900': '#212121',
      'perfect-scrollbar-thumb': '#dbdade',
      'skin-bordered-background': '#fff',
      'skin-bordered-surface': '#fff',
      'expansion-panel-text-custom-bg': '#fafafa',
      'track-bg': '#F0F2F8',
      'chat-bg': '#F7F6FA',
    },
    variables: {
      'code-color': '#d400ff',
      'overlay-scrim-background': '#2E263D',
      'tooltip-background': '#1A0E33',
      'overlay-scrim-opacity': 0.5,
      'hover-opacity': 0.04,
      'focus-opacity': 0.1,
      'selected-opacity': 0.08,
      'activated-opacity': 0.16,
      'pressed-opacity': 0.14,
      'dragged-opacity': 0.1,
      'disabled-opacity': 0.4,
      'border-color': '#2E263D',
      'border-opacity': 0.12,
      'table-header-color': '#F6F7FB',
      'high-emphasis-opacity': 0.9,
      'medium-emphasis-opacity': 0.7,

      // 👉 shadows
      'shadow-key-umbra-color': '#2E263D',
      'shadow-xs-opacity': '0.16',
      'shadow-sm-opacity': '0.18',
      'shadow-md-opacity': '0.20',
      'shadow-lg-opacity': '0.22',
      'shadow-xl-opacity': '0.24',
    },
  },
  dark: {
    dark: true,
    colors: {
      'primary': staticPrimaryColor,
      'on-primary': '#fff',
      'primary-darken-1': '#7E4EE6',
      'secondary': '#94A3B8',
      'secondary-darken-1': '#64748B',
      'on-secondary': '#fff',
      'success': '#56CA00',
      'success-darken-1': '#4DB600',
      'on-success': '#fff',
      'info': '#16B1FF',
      'info-darken-1': '#149FE6',
      'on-info': '#fff',
      'warning': '#FFB400',
      'warning-darken-1': '#E6A200',
      'on-warning': '#fff',
      'error': '#FF4C51',
      'error-darken-1': '#E64449',
      'on-error': '#fff',
      // dark slate (azul-gris)
      'background': '#0B1220',
      'on-background': '#F1F5F9',
      'surface': '#111827',
      'on-surface': '#F1F5F9',
      'grey-50': '#0F172A',
      'grey-100': '#1E293B',
      'grey-200': '#334155',
      'grey-300': '#475569',
      'grey-400': '#64748B',
      'grey-500': '#94A3B8',
      'grey-600': '#CBD5E1',
      'grey-700': '#E2E8F0',
      'grey-800': '#F1F5F9',
      'grey-900': '#F8FAFC',
      'perfect-scrollbar-thumb': '#334155',
      'skin-bordered-background': '#111827',
      'skin-bordered-surface': '#111827',
      'expansion-panel-text-custom-bg': '#1E293B',
      'track-bg': '#334155',
      'chat-bg': '#0F172A',
    },
    variables: {
      'code-color': '#d400ff',
      'overlay-scrim-background': '#020617',
      'tooltip-background': '#F8FAFC',
      'overlay-scrim-opacity': 0.55,
      'hover-opacity': 0.06,
      'focus-opacity': 0.1,
      'selected-opacity': 0.08,
      'activated-opacity': 0.16,
      'pressed-opacity': 0.14,
      'disabled-opacity': 0.4,
      'dragged-opacity': 0.1,
      'border-color': '#F1F5F9',
      'border-opacity': 0.1,
      'table-header-color': '#1E293B',
      'high-emphasis-opacity': 0.92,
      'medium-emphasis-opacity': 0.68,

      // 👉 Shadows
      'shadow-key-umbra-color': '#020617',
      'shadow-xs-opacity': '0.28',
      'shadow-sm-opacity': '0.32',
      'shadow-md-opacity': '0.36',
      'shadow-lg-opacity': '0.40',
      'shadow-xl-opacity': '0.44',
    },
  },
}
export default themes
