import { afterEach, describe, expect, it } from 'vitest'

import { i18n, setLocale } from '@/i18n'

describe('i18n', () => {
  afterEach(() => setLocale('pl'))

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
})
