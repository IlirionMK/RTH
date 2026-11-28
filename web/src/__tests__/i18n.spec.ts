import { afterEach, describe, expect, it } from 'vitest'
import { nextTick } from 'vue'

import { i18n, setLocale } from '@/i18n'
import en from '@/locales/en.json'
import pl from '@/locales/pl.json'

function keys(messages: object, prefix = ''): string[] {
  return Object.entries(messages).flatMap(([key, value]) =>
    typeof value === 'object' && value !== null ? keys(value, `${prefix}${key}.`) : [`${prefix}${key}`],
  )
}

describe('i18n', () => {
  afterEach(() => {
    setLocale('pl')
    localStorage.clear()
  })

  it('uses Polish by default', () => {
    expect(i18n.global.locale.value).toBe('pl')
    expect(i18n.global.t('nav.trips')).toBe('Podróże')
  })

  it('switches the interface to English', () => {
    setLocale('en')

    expect(i18n.global.t('nav.trips')).toBe('Trips')
  })

  it('sets the page language together with the interface language', () => {
    setLocale('en')

    expect(document.documentElement.lang).toBe('en')
  })

  it('remembers the language in the browser', async () => {
    setLocale('en')
    await nextTick()

    expect(localStorage.getItem('rth-locale')).toBe('en')
  })

  it('has every text in both languages', () => {
    expect(keys(en).sort()).toEqual(keys(pl).sort())
  })
})
