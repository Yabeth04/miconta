import { axios } from '@/plugins/axios'
import { useAuthStore } from '@/stores/auth'
import { createRouter, createWebHistory } from 'vue-router'
import { routes } from './routes'

const router = createRouter({
  history: createWebHistory('/'),
  routes,
})

router.beforeEach(async to => {
  const auth = useAuthStore()

  if (!auth.initialized) {
    await auth.initialize()
  }

  const needsAuth = to.matched.some(record => record.meta.auth === true)
  const isGuest = to.matched.some(record => record.meta.guest === true)

  if (needsAuth && !auth.isAuthenticated) {
    return { path: '/login', query: { redirect: to.fullPath } }
  }

  if (isGuest && auth.isAuthenticated) {
    return { path: '/dashboard' }
  }

  const requiredRole = to.matched.map(record => record.meta.role).find(Boolean)
  if (requiredRole && auth.roleName !== requiredRole) {
    return { path: '/dashboard' }
  }

  return true
})

axios.interceptors.response.use(
  response => response,
  error => {
    if (error.response?.status === 401) {
      const auth = useAuthStore()
      const onLogin = router.currentRoute.value.path === '/login'

      // Evitar bucles: solo limpiar sesión; el guard redirige si hace falta
      if (auth.user) {
        auth.user = null
      }

      if (!onLogin && auth.initialized && router.currentRoute.value.meta?.auth) {
        router.replace({
          path: '/login',
          query: { redirect: router.currentRoute.value.fullPath },
        })
      }
    }

    return Promise.reject(error)
  },
)

export default function (app) {
  app.use(router)

  router.isReady().then(() => {
    const prefetch = () => {
      import('@/pages/moduleAccounting.vue')
      import('@/pages/moduleStudyPlan.vue')
      import('@/pages/moduleAccountingConcepts.vue')
    }

    if (typeof requestIdleCallback === 'function')
      requestIdleCallback(prefetch)
    else
      setTimeout(prefetch, 200)
  })
}

export { router }
