<script setup lang="ts">
import { Search, Users } from '@lucide/vue'
import { watchDebounced } from '@vueuse/core'
import { onMounted, ref } from 'vue'

import { listPublicProfiles } from '@/api/publicProfiles'
import PageHeader from '@/components/PageHeader.vue'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { Empty, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty'
import { Field, FieldDescription, FieldLabel } from '@/components/ui/field'
import { InputGroup, InputGroupAddon, InputGroupInput } from '@/components/ui/input-group'
import { Skeleton } from '@/components/ui/skeleton'
import { Spinner } from '@/components/ui/spinner'
import { initials } from '@/lib/format'
import type { PublicProfile } from '@/types/profile'

const query = ref('')
const profiles = ref<PublicProfile[]>([])
const page = ref(0)
const lastPage = ref(1)
const state = ref<'loading' | 'error' | 'ready'>('loading')
const loadingMore = ref(false)
let latest = 0

async function loadPage(next: number): Promise<void> {
  const request = ++latest
  const term = query.value.trim()
  const result = await listPublicProfiles(term.length >= 2 ? term : '', next)

  if (request !== latest) {
    return
  }

  profiles.value = next === 1 ? result.data : [...profiles.value, ...result.data]
  page.value = result.meta.current_page
  lastPage.value = result.meta.last_page
}

async function load(): Promise<void> {
  state.value = 'loading'

  try {
    await loadPage(1)
    state.value = 'ready'
  } catch {
    state.value = 'error'
  }
}

async function loadMore(): Promise<void> {
  loadingMore.value = true

  try {
    await loadPage(page.value + 1)
  } catch {
    state.value = 'error'
  } finally {
    loadingMore.value = false
  }
}

watchDebounced(
  query,
  (value, previous) => {
    const [now, before] = [value.trim(), previous.trim()]

    if (now.length >= 2 || (before.length >= 2 && now.length < 2)) {
      load()
    }
  },
  { debounce: 300 },
)

onMounted(load)
</script>

<template>
  <div class="flex max-w-5xl flex-col gap-6">
    <PageHeader :title="$t('discover.title')" :description="$t('discover.description')" />

    <Field class="max-w-md">
      <FieldLabel for="profile-search" class="sr-only">{{ $t('discover.searchLabel') }}</FieldLabel>
      <InputGroup>
        <InputGroupAddon>
          <Search aria-hidden="true" />
        </InputGroupAddon>
        <InputGroupInput
          id="profile-search"
          v-model="query"
          type="search"
          :placeholder="$t('discover.search')"
          aria-describedby="profile-search-hint"
        />
      </InputGroup>
      <FieldDescription id="profile-search-hint">{{ $t('discover.hint') }}</FieldDescription>
    </Field>

    <div v-if="state === 'loading'" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3" :aria-label="$t('common.loading')" aria-busy="true">
      <Skeleton v-for="index in 3" :key="index" class="h-40 rounded-xl" />
    </div>

    <Alert v-else-if="state === 'error'" variant="destructive">
      <AlertTitle>{{ $t('discover.loadError') }}</AlertTitle>
      <AlertDescription>
        <Button variant="outline" size="sm" class="mt-2" @click="load">{{ $t('trips.retry') }}</Button>
      </AlertDescription>
    </Alert>

    <Empty v-else-if="profiles.length === 0" class="border">
      <EmptyHeader>
        <EmptyMedia variant="icon">
          <Users aria-hidden="true" />
        </EmptyMedia>
        <EmptyTitle>{{ $t(query.trim().length >= 2 ? 'discover.notFound' : 'discover.empty') }}</EmptyTitle>
        <EmptyDescription>{{ $t('discover.emptyHint') }}</EmptyDescription>
      </EmptyHeader>
    </Empty>

    <template v-else>
      <ul class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <li v-for="profile in profiles" :key="profile.id">
          <Card class="h-full">
            <CardContent class="flex h-full flex-col gap-4">
              <div class="flex items-center gap-3">
                <Avatar class="size-12">
                  <AvatarFallback>{{ initials(profile.name) }}</AvatarFallback>
                </Avatar>
                <div class="flex min-w-0 flex-col">
                  <h2 class="truncate font-semibold">{{ profile.name }}</h2>
                  <p class="text-sm text-muted-foreground tabular-nums">
                    {{ $t('discover.summary', { trips: profile.trips_count ?? 0, countries: profile.countries_count ?? 0 }) }}
                  </p>
                </div>
              </div>
              <Button variant="outline" class="mt-auto" as-child>
                <RouterLink :to="{ name: 'discover.show', params: { id: profile.id } }" :aria-label="$t('discover.openNamed', { name: profile.name })">
                  {{ $t('discover.open') }}
                </RouterLink>
              </Button>
            </CardContent>
          </Card>
        </li>
      </ul>
      <Button v-if="page < lastPage" variant="outline" class="self-center" :disabled="loadingMore" @click="loadMore">
        <Spinner v-if="loadingMore" :aria-label="$t('common.loading')" />
        {{ $t('trips.more') }}
      </Button>
    </template>
  </div>
</template>
