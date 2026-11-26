import { describe, expect, it } from 'vitest'

import { flightArc } from '@/lib/map'

describe('flightArc', () => {
  it('starts and ends where the flight does', () => {
    const arc = flightArc([[-8.6291, 41.1579], [25.2797, 54.6872]])

    expect(arc).toHaveLength(49)
    expect(arc[0]).toEqual([-8.6291, 41.1579])
    expect(arc[48]?.[0]).toBeCloseTo(25.2797)
    expect(arc[48]?.[1]).toBeCloseTo(54.6872)
  })

  it('bends to the north whichever way the plane flies', () => {
    const east = flightArc([[0, 50], [20, 50]])
    const west = flightArc([[20, 50], [0, 50]])

    expect(east[24]?.[1]).toBeGreaterThan(50)
    expect(west[24]?.[1]).toBeGreaterThan(50)
  })

  it('keeps a line that has no two ends', () => {
    expect(flightArc([[0, 50]])).toEqual([[0, 50]])
  })
})
