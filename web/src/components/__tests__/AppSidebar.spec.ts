import { enableAutoUnmount, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { afterEach, describe, expect, it } from 'vitest'

import AppSidebar from '@/components/AppSidebar.vue'
import { SidebarProvider } from '@/components/ui/sidebar'
import { i18n } from '@/i18n'
import router from '@/router'
import { useAuthStore } from '@/stores/auth'
import type { User } from '@/types/user'

const user: User = {
  id: 1,
  name: 'Test User',
  email: 'test@example.com',
  pending_email: null,
  role: 'user',
  is_public: false,
  locale: 'pl',
  theme: 'light',
}

function mountSidebar(role: User['role']) {
  const pinia = createPinia()
  setActivePinia(pinia)
  useAuthStore().setUser({ ...user, role })

  return mount(SidebarProvider, { slots: { default: AppSidebar }, global: { plugins: [pinia, router, i18n] } })
}

enableAutoUnmount(afterEach)

describe('AppSidebar', () => {
  it('hides the administration from a regular user', () => {
    const wrapper = mountSidebar('user')

    expect(wrapper.text()).toContain('Podróże')
    expect(wrapper.text()).not.toContain('Administracja')
    expect(wrapper.find('a[href="/admin/users"]').exists()).toBe(false)
  })

  it('shows the administration to an administrator', () => {
    const wrapper = mountSidebar('admin')

    expect(wrapper.text()).toContain('Administracja')
    expect(wrapper.find('a[href="/admin/users"]').exists()).toBe(true)
  })
})
