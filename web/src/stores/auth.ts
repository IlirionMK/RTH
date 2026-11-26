import { defineStore } from 'pinia'
import { computed, ref, watch } from 'vue'

import { createToken, deleteToken, register as createUser } from '@/api/auth'
import type { LoginPayload, RegisterPayload } from '@/api/auth'
import { getProfile, updateProfile } from '@/api/profile'
import { guestTheme } from '@/composables/useTheme'
import { token } from '@/lib/api'
import type { User } from '@/types/user'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)

  const isAuthenticated = computed(() => token.value !== null)
  const isAdmin = computed(() => user.value?.role === 'admin')

  watch(token, (value) => {
    if (value === null) {
      user.value = null
    }
  })

  async function login(payload: LoginPayload): Promise<void> {
    const result = await createToken(payload)

    token.value = result.token
    user.value = result.user

    if (guestTheme.value && guestTheme.value !== result.user.theme) {
      user.value = await updateProfile({ theme: guestTheme.value }).catch(() => result.user)
    }

    guestTheme.value = null
  }

  async function register(payload: RegisterPayload): Promise<void> {
    await createUser(payload)
  }

  async function logout(): Promise<void> {
    await deleteToken().catch(() => undefined)

    token.value = null
    user.value = null
  }

  async function loadUser(): Promise<void> {
    if (user.value === null) {
      user.value = await getProfile()
    }
  }

  function setUser(value: User): void {
    user.value = value
  }

  return { user, isAuthenticated, isAdmin, login, register, logout, loadUser, setUser }
})
