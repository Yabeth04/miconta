import { createRouter, createWebHistory } from 'vue-router'
import { routes } from './routes'

const router = createRouter({
  // Laravel Vite usa base `/build/` para assets; las rutas SPA deben quedar en `/`
  history: createWebHistory('/'),
  routes,
})

export default function (app) {
  app.use(router)
}
export { router }
