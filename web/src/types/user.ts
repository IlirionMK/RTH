import type { Theme } from '@/composables/useTheme'
import type { Locale } from '@/i18n'

export type Role = 'user' | 'admin'

export interface User {
  id: number
  name: string
  email: string
  role: Role
  is_public: boolean
  locale: Locale
  theme: Theme
}
