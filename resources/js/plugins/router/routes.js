export const routes = [
  { path: '/', redirect: '/dashboard' },
  {
    path: '/',
    component: () => import('@/layouts/default.vue'),
    meta: { auth: true },
    children: [
      {
        path: 'dashboard',
        component: () => import('@/pages/dashboard.vue'),
        meta: { auth: true },
      },
      {
        path: 'account-settings',
        component: () => import('@/pages/account-settings.vue'),
        meta: { auth: true },
      },
      {
        path: 'contabilidad',
        component: () => import('@/pages/moduleAccounting.vue'),
        meta: { auth: true },
      },
      {
        path: 'contabilidad/conceptos',
        component: () => import('@/pages/moduleAccountingConcepts.vue'),
        meta: { auth: true },
      },
      {
        path: 'pagos-fijos',
        component: () => import('@/pages/moduleFixedPayments.vue'),
        meta: { auth: true },
      },
      {
        path: 'proyeccion',
        component: () => import('@/pages/moduleProjection.vue'),
        meta: { auth: true },
      },
      {
        path: 'plan-estudios',
        component: () => import('@/pages/moduleStudyPlan.vue'),
        meta: { auth: true, role: 'sysAdmin' },
      },
      {
        path: 'usuarios',
        component: () => import('@/pages/moduleUsers.vue'),
        meta: { auth: true, role: 'sysAdmin' },
      },
    ],
  },
  {
    path: '/',
    component: () => import('@/layouts/blank.vue'),
    children: [
      {
        path: 'login',
        component: () => import('@/pages/login.vue'),
        meta: { guest: true },
      },
      {
        path: 'register',
        component: () => import('@/pages/register.vue'),
        meta: { guest: true },
      },
      {
        path: '/:pathMatch(.*)*',
        component: () => import('@/pages/[...error].vue'),
      },
    ],
  },
]
