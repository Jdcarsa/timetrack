import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/LoginView.vue'),
    meta: { guest: true },
  },
  {
    path: '/',
    component: () => import('@/views/LayoutView.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'Dashboard',
        component: () => import('@/views/DashboardView.vue'),
      },
      {
        path: 'historial',
        name: 'Historial',
        component: () => import('@/views/HistorialView.vue'),
      },
      {
        path: 'planificacion',
        name: 'Planificacion',
        component: () => import('@/views/PlanificacionView.vue'),
      },
      // Rutas solo admin
      {
        path: 'admin/empleados',
        name: 'AdminEmpleados',
        component: () => import('@/views/admin/EmpleadosView.vue'),
        meta: { adminOnly: true },
      },
      {
        path: 'admin/registros',
        name: 'AdminRegistros',
        component: () => import('@/views/admin/RegistrosView.vue'),
        meta: { adminOnly: true },
      },
      {
        path: 'admin/export',
        name: 'AdminExport',
        component: () => import('@/views/admin/ExportView.vue'),
        meta: { adminOnly: true },
      },
      {
        path: 'admin/calendario',
        name: 'AdminCalendario',
        component: () => import('@/views/admin/CalendarioView.vue'),
        meta: { adminOnly: true },
      },
    ],
  },
  { path: '/:pathMatch(.*)*', redirect: '/' },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

// Guards de navegación
router.beforeEach((to) => {
  const auth = useAuthStore()

  if (to.meta.requiresAuth && !auth.isLoggedIn) return '/login'
  if (to.meta.guest && auth.isLoggedIn) return '/'
  if (to.meta.adminOnly && !auth.isAdmin) return '/'
})

export default router
