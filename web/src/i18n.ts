import { useLocalStorage } from '@vueuse/core'
import { createI18n } from 'vue-i18n'

import en from '@/locales/en.json'
import pl from '@/locales/pl.json'

export type Locale = 'pl' | 'en'

const savedLocale = useLocalStorage<Locale>('rth-locale', 'pl')

export const guestLocale = useLocalStorage<Locale | null>('rth-guest-locale', null)

export const i18n = createI18n({
  legacy: false,
  locale: savedLocale.value === 'en' ? 'en' : 'pl',
  fallbackLocale: 'en',
  messages: { pl, en },
})

document.documentElement.lang = i18n.global.locale.value

export function setLocale(locale: Locale): void {
  i18n.global.locale.value = locale
  savedLocale.value = locale
  document.documentElement.lang = locale
}
