import type { Transport } from '@/types/trip'

export const transportLines: Record<Transport, { color: string; dash: number[] | null }> = {
  car: { color: '#2563eb', dash: null },
  plane: { color: '#ea580c', dash: [2, 1.5] },
  train: { color: '#047857', dash: [0, 2] },
  other: { color: '#9333ea', dash: [3, 1.5, 0.5, 1.5] },
}

export const visitedCountryColor = '#6366f1'

export const pointColors = {
  light: { fill: '#0f172a', stroke: '#ffffff', text: '#0f172a', halo: '#ffffff' },
  dark: { fill: '#f8fafc', stroke: '#0f172a', text: '#f8fafc', halo: '#0f172a' },
}

const ARC_BEND = 0.2

const ARC_STEPS = 48

export function flightArc(coordinates: [number, number][]): [number, number][] {
  const start = coordinates[0]
  const end = coordinates[coordinates.length - 1]

  if (!start || !end || coordinates.length < 2) {
    return coordinates
  }

  const [dx, dy] = [end[0] - start[0], end[1] - start[1]]
  const side = dx >= 0 ? 1 : -1
  const control: [number, number] = [
    (start[0] + end[0]) / 2 - dy * ARC_BEND * side,
    (start[1] + end[1]) / 2 + dx * ARC_BEND * side,
  ]

  return Array.from({ length: ARC_STEPS + 1 }, (_, step) => {
    const t = step / ARC_STEPS

    return [
      (1 - t) ** 2 * start[0] + 2 * (1 - t) * t * control[0] + t ** 2 * end[0],
      (1 - t) ** 2 * start[1] + 2 * (1 - t) * t * control[1] + t ** 2 * end[1],
    ]
  })
}
