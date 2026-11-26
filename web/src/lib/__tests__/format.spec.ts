import { describe, expect, it } from 'vitest'

import { countryName, formatDates, formatDistance } from '@/lib/format'

describe('format', () => {
  it('shows one date for a one-day trip', () => {
    expect(formatDates('2026-07-12', null, 'en')).toBe('Jul 12, 2026')
    expect(formatDates('2026-07-12', '2026-07-12', 'en')).toBe('Jul 12, 2026')
  })

  it('shows both dates of a longer trip', () => {
    expect(formatDates('2026-07-12', '2026-07-19', 'en')).toBe('Jul 12, 2026 – Jul 19, 2026')
  })

  it('rounds the distance to whole kilometres', () => {
    expect(formatDistance(4115.4, 'en')).toBe('4,115 km')
  })

  it('names countries in the interface language', () => {
    expect(countryName('DE', 'pl')).toBe('Niemcy')
    expect(countryName('DE', 'en')).toBe('Germany')
  })
})
