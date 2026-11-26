import { Car, Plane, Route, TrainFront } from '@lucide/vue'
import type { Component } from 'vue'

import type { Transport } from '@/types/trip'

export const transports: Transport[] = ['car', 'plane', 'train', 'other']

export const transportIcons: Record<Transport, Component> = {
  car: Car,
  plane: Plane,
  train: TrainFront,
  other: Route,
}
