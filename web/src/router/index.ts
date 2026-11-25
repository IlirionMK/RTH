import { createRouter, createWebHistory } from 'vue-router'

import AppLayout from '@/layouts/AppLayout.vue'
import AuthLayout from '@/layouts/AuthLayout.vue'
import { token } from '@/lib/api'
import { useAuthStore } from '@/stores/auth'
import LoginView from '@/views/LoginView.vue'
import PlaceholderView from '@/views/PlaceholderView.vue'
import ProfileView from '@/views/ProfileView.vue'
import RegisterView from '@/views/RegisterView.vue'

declare module 'vue-router' {
  interface RouteMeta {
    guest?: boolean
    requiresAuth?: boolean
    admin?: boolean
  }
}

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      component: AppLayout,
      meta: { requiresAuth: true },
      children: [
        { path: '', redirect: { name: 'trips.index' } },
        {
          path: 'trips',
          name: 'trips.index',
          component: PlaceholderView,
          props: { title: 'trips.title', description: 'trips.description' },
        },
        { path: 'map', name: 'map', component: PlaceholderView, props: { title: 'nav.map' } },
        { path: 'stats', name: 'stats', component: PlaceholderView, props: { title: 'nav.stats' } },
        { path: 'discover', name: 'discover', component: PlaceholderView, props: { title: 'nav.discover' } },
        { path: 'profile', name: 'profile', component: ProfileView },
        {
          path: 'admin',
          meta: { admin: true },
          children: [
            { path: '', name: 'admin.dashboard', component: PlaceholderView, props: { title: 'nav.dashboard' } },
            { path: 'users', name: 'admin.users', component: PlaceholderView, props: { title: 'nav.users' } },
            { path: 'trips', name: 'admin.trips', component: PlaceholderView, props: { title: 'nav.userTrips' } },
          ],
        },
      ],
    },
    {
      path: '/',
      component: AuthLayout,
      meta: { guest: true },
      children: [
        { path: 'login', name: 'login', component: LoginView },
        { path: 'register', name: 'register', component: RegisterView },
      ],
    },
  ],
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  if (to.meta.guest && auth.isAuthenticated) {
    return { name: 'trips.index' }
  }

  if (!to.meta.requiresAuth) {
    return true
  }

  if (!auth.isAuthenticated) {
    return { name: 'login' }
  }

  try {
    await auth.loadUser()
  } catch {
    token.value = null

    return { name: 'login' }
  }

  if (to.meta.admin && !auth.isAdmin) {
    return { name: 'trips.index' }
  }

  return true
})

export default router
