<script setup lang="ts">
import { Ban, Check, Search } from '@lucide/vue'
import { watchDebounced } from '@vueuse/core'
import { onMounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { toast } from 'vue-sonner'

import { listAdminUsers, updateAdminUser, type AdminUserFilters } from '@/api/admin'
import PageHeader from '@/components/PageHeader.vue'
import PagePager from '@/components/PagePager.vue'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { InputGroup, InputGroupAddon, InputGroupInput } from '@/components/ui/input-group'
import { NativeSelect, NativeSelectOption } from '@/components/ui/native-select'
import { Skeleton } from '@/components/ui/skeleton'
import { Spinner } from '@/components/ui/spinner'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { useAuthStore } from '@/stores/auth'
import type { AdminUser } from '@/types/admin'
import type { Role } from '@/types/user'

const { t, locale } = useI18n()
const auth = useAuthStore()

const filters = reactive<AdminUserFilters>({ query: '', role: '', status: '', page: 1 })
const users = ref<AdminUser[]>([])
const meta = ref({ current_page: 1, last_page: 1, total: 0 })
const state = ref<'loading' | 'error' | 'ready'>('loading')
const blocking = ref<AdminUser | null>(null)
const saving = ref(false)
let latest = 0

async function load(): Promise<void> {
  const request = ++latest

  try {
    const page = await listAdminUsers({ ...filters, query: filters.query.trim() })

    if (request === latest) {
      users.value = page.data
      meta.value = page.meta
      state.value = 'ready'
    }
  } catch {
    state.value = 'error'
  }
}

function retry(): void {
  state.value = 'loading'
  load()
}

function goTo(page: number): void {
  filters.page = page
  load()
}

function replace(updated: AdminUser): void {
  users.value = users.value.map((user) => (user.id === updated.id ? { ...updated, trips_count: user.trips_count } : user))
}

async function changeRole(user: AdminUser, role: Role): Promise<void> {
  try {
    replace(await updateAdminUser(user.id, { role }))
    toast.success(t('admin.roleSaved', { name: user.name }))
  } catch {
    toast.error(t('common.error'))
    replace({ ...user })
  }
}

async function toggleBlock(): Promise<void> {
  const user = blocking.value

  if (!user) {
    return
  }

  saving.value = true

  try {
    replace(await updateAdminUser(user.id, { is_blocked: !user.is_blocked }))
    toast.success(t(user.is_blocked ? 'admin.unblocked' : 'admin.blocked', { name: user.name }))
    blocking.value = null
  } catch {
    toast.error(t('common.error'))
  } finally {
    saving.value = false
  }
}

function formatDate(value: string): string {
  return new Intl.DateTimeFormat(locale.value, { dateStyle: 'medium' }).format(new Date(value))
}

function isSelf(user: AdminUser): boolean {
  return user.id === auth.user?.id
}

watchDebounced(
  () => filters.query,
  () => goTo(1),
  { debounce: 300 },
)
watch(() => [filters.role, filters.status], () => goTo(1))
onMounted(load)
</script>

<template>
  <div class="flex flex-col gap-6">
    <PageHeader :title="$t('admin.usersTitle')" :description="$t('admin.usersCount', { count: meta.total })" />

    <div class="flex flex-col gap-3 md:flex-row">
      <InputGroup class="md:max-w-md">
        <InputGroupAddon>
          <Search aria-hidden="true" />
        </InputGroupAddon>
        <InputGroupInput v-model="filters.query" type="search" :placeholder="$t('admin.searchUsers')" :aria-label="$t('admin.searchUsers')" />
      </InputGroup>
      <NativeSelect v-model="filters.role" :aria-label="$t('admin.role')">
        <NativeSelectOption value="">{{ $t('admin.allRoles') }}</NativeSelectOption>
        <NativeSelectOption value="user">{{ $t('admin.roles.user') }}</NativeSelectOption>
        <NativeSelectOption value="admin">{{ $t('admin.roles.admin') }}</NativeSelectOption>
      </NativeSelect>
      <NativeSelect v-model="filters.status" :aria-label="$t('admin.status')">
        <NativeSelectOption value="">{{ $t('admin.allStatuses') }}</NativeSelectOption>
        <NativeSelectOption value="active">{{ $t('admin.active') }}</NativeSelectOption>
        <NativeSelectOption value="blocked">{{ $t('admin.blockedStatus') }}</NativeSelectOption>
      </NativeSelect>
    </div>

    <div v-if="state === 'loading'" :aria-label="$t('common.loading')" aria-busy="true">
      <Skeleton class="h-96 rounded-xl" />
    </div>

    <Alert v-else-if="state === 'error'" variant="destructive">
      <AlertTitle>{{ $t('admin.loadError') }}</AlertTitle>
      <AlertDescription>
        <Button variant="outline" size="sm" class="mt-2" @click="retry">{{ $t('trips.retry') }}</Button>
      </AlertDescription>
    </Alert>

    <p v-else-if="users.length === 0" class="text-muted-foreground">{{ $t('admin.noUsers') }}</p>

    <Card v-else class="py-0">
      <CardContent class="flex flex-col gap-4 p-0 pb-4">
        <Table class="hidden md:table">
          <TableHeader>
            <TableRow>
              <TableHead class="pl-4">{{ $t('admin.name') }}</TableHead>
              <TableHead>{{ $t('admin.email') }}</TableHead>
              <TableHead>{{ $t('admin.role') }}</TableHead>
              <TableHead>{{ $t('admin.status') }}</TableHead>
              <TableHead class="text-right">{{ $t('admin.trips') }}</TableHead>
              <TableHead>{{ $t('admin.created') }}</TableHead>
              <TableHead class="pr-4 text-right">{{ $t('admin.actions') }}</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="user in users" :key="user.id">
              <TableCell class="pl-4 font-medium">{{ user.name }}</TableCell>
              <TableCell>{{ user.email }}</TableCell>
              <TableCell>
                <NativeSelect
                  size="sm"
                  :model-value="user.role"
                  :disabled="isSelf(user)"
                  :aria-label="$t('admin.roleOf', { name: user.name })"
                  @update:model-value="changeRole(user, $event as Role)"
                >
                  <NativeSelectOption value="user">{{ $t('admin.roles.user') }}</NativeSelectOption>
                  <NativeSelectOption value="admin">{{ $t('admin.roles.admin') }}</NativeSelectOption>
                </NativeSelect>
              </TableCell>
              <TableCell>
                <Badge :variant="user.is_blocked ? 'destructive' : 'outline'">
                  <component :is="user.is_blocked ? Ban : Check" aria-hidden="true" />
                  {{ $t(user.is_blocked ? 'admin.blockedStatus' : 'admin.active') }}
                </Badge>
              </TableCell>
              <TableCell class="text-right tabular-nums">{{ user.trips_count }}</TableCell>
              <TableCell class="tabular-nums">{{ formatDate(user.created_at) }}</TableCell>
              <TableCell class="pr-4 text-right">
                <Button
                  :variant="user.is_blocked ? 'outline' : 'destructive'"
                  size="sm"
                  :disabled="isSelf(user)"
                  :aria-label="$t(user.is_blocked ? 'admin.unblockNamed' : 'admin.blockNamed', { name: user.name })"
                  @click="blocking = user"
                >
                  {{ $t(user.is_blocked ? 'admin.unblock' : 'admin.block') }}
                </Button>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>

        <ul class="flex flex-col divide-y md:hidden">
          <li v-for="user in users" :key="user.id" class="flex flex-col gap-3 p-4">
            <div class="flex items-start justify-between gap-3">
              <div class="flex min-w-0 flex-col">
                <span class="truncate font-medium">{{ user.name }}</span>
                <span class="truncate text-sm text-muted-foreground">{{ user.email }}</span>
              </div>
              <Badge :variant="user.is_blocked ? 'destructive' : 'outline'">
                {{ $t(user.is_blocked ? 'admin.blockedStatus' : 'admin.active') }}
              </Badge>
            </div>
            <p class="text-sm text-muted-foreground tabular-nums">
              {{ $t('admin.cardSummary', { trips: user.trips_count, date: formatDate(user.created_at) }) }}
            </p>
            <div class="flex flex-wrap gap-2">
              <NativeSelect
                size="sm"
                :model-value="user.role"
                :disabled="isSelf(user)"
                :aria-label="$t('admin.roleOf', { name: user.name })"
                @update:model-value="changeRole(user, $event as Role)"
              >
                <NativeSelectOption value="user">{{ $t('admin.roles.user') }}</NativeSelectOption>
                <NativeSelectOption value="admin">{{ $t('admin.roles.admin') }}</NativeSelectOption>
              </NativeSelect>
              <Button
                :variant="user.is_blocked ? 'outline' : 'destructive'"
                size="sm"
                :disabled="isSelf(user)"
                :aria-label="$t(user.is_blocked ? 'admin.unblockNamed' : 'admin.blockNamed', { name: user.name })"
                @click="blocking = user"
              >
                {{ $t(user.is_blocked ? 'admin.unblock' : 'admin.block') }}
              </Button>
            </div>
          </li>
        </ul>

        <PagePager
          class="px-4"
          :page="meta.current_page"
          :last-page="meta.last_page"
          :total="meta.total"
          :per-page="15"
          @change="goTo"
        />
      </CardContent>
    </Card>

    <Dialog :open="blocking !== null" @update:open="!$event && (blocking = null)">
      <DialogContent :show-close-button="false">
        <DialogHeader>
          <DialogTitle>{{ $t(blocking?.is_blocked ? 'admin.unblockTitle' : 'admin.blockTitle', { name: blocking?.name }) }}</DialogTitle>
          <DialogDescription>
            {{ $t(blocking?.is_blocked ? 'admin.unblockDescription' : 'admin.blockDescription') }}
          </DialogDescription>
        </DialogHeader>
        <DialogFooter>
          <DialogClose as-child>
            <Button variant="outline">{{ $t('trip.delete.cancel') }}</Button>
          </DialogClose>
          <Button :variant="blocking?.is_blocked ? 'default' : 'destructive'" :disabled="saving" @click="toggleBlock">
            <Spinner v-if="saving" :aria-label="$t('common.loading')" />
            {{ $t(blocking?.is_blocked ? 'admin.unblock' : 'admin.block') }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>
