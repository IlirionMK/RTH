import { nextTick } from 'vue'
import { createRouter, createWebHistory } from 'vue-router'

import AppLayout from '@/layouts/AppLayout.vue'
import AuthLayout from '@/layouts/AuthLayout.vue'
import { token } from '@/lib/api'
import { canAnimate } from '@/lib/transitions'
import { useAuthStore } from '@/stores/auth'
import EmailConfirmationView from '@/views/EmailConfirmationView.vue'
import LoginView from '@/views/LoginView.vue'
import PlaceholderView from '@/views/PlaceholderView.vue'
import ProfileView from '@/views/ProfileView.vue'
import RegisterView from '@/views/RegisterView.vue'
import TripsView from '@/views/TripsView.vue'

declare module 'vue-router' {
  interface RouteMeta {
    guest?: boolean
    requiresAuth?: boolean
    admin?: boolean
    nav?: string
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
        { path: 'trips', name: 'trips.index', component: TripsView },
        { path: 'trips/new', name: 'trips.create', component: () => import('@/views/TripEditView.vue'), meta: { nav: 'trips.index' } },
        {
          path: 'trips/:id/edit',
          name: 'trips.edit',
          component: () => import('@/views/TripEditView.vue'),
          props: (route) => ({ id: Number(route.params.id) }),
          meta: { nav: 'trips.index' },
        },
        { path: 'map', name: 'map', component: () => import('@/views/MapView.vue') },
        {
          path: 'trips/:id/map',
          name: 'trips.map',
          component: () => import('@/views/TripMapView.vue'),
          props: (route) => ({ id: Number(route.params.id) }),
          meta: { nav: 'trips.index' },
        },
        { path: 'stats', name: 'stats', component: () => import('@/views/StatsView.vue') },
        { path: 'discover', name: 'discover', component: () => import('@/views/DiscoverView.vue') },
        {
          path: 'discover/:id',
          name: 'discover.show',
          component: () => import('@/views/PublicProfileView.vue'),
          props: (route) => ({ id: Number(route.params.id) }),
          meta: { nav: 'discover' },
        },
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
    {
      path: '/',
      component: AuthLayout,
      children: [
        {
          path: 'email-verifications/:user/:hash',
          name: 'email-verification',
          component: EmailConfirmationView,
          props: { kind: 'verification' },
        },
        {
          path: 'email-changes/:user/:hash',
          name: 'email-change',
          component: EmailConfirmationView,
          props: { kind: 'change' },
        },
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

let finishTransition: (() => void) | null = null

router.beforeResolve(async (to, from) => {
  if (!canAnimate() || from.matched.length === 0 || to.fullPath === from.fullPath) {
    return
  }

  await new Promise<void>((resolveNavigation) => {
    document.startViewTransition(
      () =>
        new Promise<void>((resolveUpdate) => {
          finishTransition = resolveUpdate
          resolveNavigation()
        }),
    )
  })
})

router.afterEach(async () => {
  await nextTick()
  finishTransition?.()
  finishTransition = null
})

export default router
