<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'

import { getSystemStatistics, listAdminUsers } from '@/api/admin'
import MonthlyChart from '@/components/MonthlyChart.vue'
import PageHeader from '@/components/PageHeader.vue'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import type { AdminUser, SystemStatistics } from '@/types/admin'

const RECENT_ACCOUNTS = 5

const { t, locale } = useI18n()

const statistics = ref<SystemStatistics | null>(null)
const recent = ref<AdminUser[]>([])
const state = ref<'loading' | 'error' | 'ready'>('loading')

const tiles = computed(() => {
  const months = statistics.value?.months ?? []
  const current = months[months.length - 1]

  return [
    { label: t('admin.users'), value: statistics.value?.users_count ?? 0 },
    { label: t('admin.trips'), value: statistics.value?.trips_count ?? 0 },
    { label: t('admin.newUsersMonth'), value: current?.users ?? 0 },
    { label: t('admin.newTripsMonth'), value: current?.trips ?? 0 },
  ]
})

function formatDate(value: string): string {
  return new Intl.DateTimeFormat(locale.value, { dateStyle: 'medium' }).format(new Date(value))
}

async function load(): Promise<void> {
  state.value = 'loading'

  try {
    const [stats, users] = await Promise.all([
      getSystemStatistics(),
      listAdminUsers({ query: '', role: '', status: '', page: 1 }),
    ])
    statistics.value = stats
    recent.value = users.data.slice(0, RECENT_ACCOUNTS)
    state.value = 'ready'
  } catch {
    state.value = 'error'
  }
}

onMounted(load)
</script>

<template>
  <div class="flex flex-col gap-6">
    <PageHeader :title="$t('admin.dashboard')" :description="$t('admin.dashboardDescription')" />

    <div v-if="state === 'loading'" class="flex flex-col gap-6" :aria-label="$t('common.loading')" aria-busy="true">
      <Skeleton class="h-28 rounded-xl" />
      <Skeleton class="h-72 rounded-xl" />
    </div>

    <Alert v-else-if="state === 'error'" variant="destructive">
      <AlertTitle>{{ $t('admin.loadError') }}</AlertTitle>
      <AlertDescription>
        <Button variant="outline" size="sm" class="mt-2" @click="load">{{ $t('trips.retry') }}</Button>
      </AlertDescription>
    </Alert>

    <template v-else-if="statistics">
      <dl class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <Card v-for="tile in tiles" :key="tile.label" class="py-4">
          <CardContent class="flex flex-col gap-1">
            <dt class="text-sm text-muted-foreground">{{ tile.label }}</dt>
            <dd class="text-3xl font-semibold tabular-nums">{{ tile.value }}</dd>
          </CardContent>
        </Card>
      </dl>

      <Card>
        <CardHeader>
          <CardTitle>{{ $t('admin.chart') }}</CardTitle>
          <CardDescription>{{ $t('admin.chartHint') }}</CardDescription>
        </CardHeader>
        <CardContent>
          <MonthlyChart :months="statistics.months" />
        </CardContent>
      </Card>

      <Card>
        <CardHeader class="flex flex-row flex-wrap items-center justify-between gap-2">
          <CardTitle>{{ $t('admin.recentUsers') }}</CardTitle>
          <RouterLink
            :to="{ name: 'admin.users' }"
            class="rounded-sm text-sm font-medium underline underline-offset-4 focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
          >
            {{ $t('admin.allUsers') }}
          </RouterLink>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>{{ $t('admin.name') }}</TableHead>
                <TableHead class="hidden sm:table-cell">{{ $t('admin.email') }}</TableHead>
                <TableHead>{{ $t('admin.created') }}</TableHead>
                <TableHead class="text-right">{{ $t('admin.trips') }}</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="user in recent" :key="user.id">
                <TableCell class="font-medium">{{ user.name }}</TableCell>
                <TableCell class="hidden sm:table-cell">{{ user.email }}</TableCell>
                <TableCell class="tabular-nums">{{ formatDate(user.created_at) }}</TableCell>
                <TableCell class="text-right tabular-nums">{{ user.trips_count }}</TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </template>
  </div>
</template>
