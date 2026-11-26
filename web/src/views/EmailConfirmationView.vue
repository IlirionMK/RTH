<script setup lang="ts">
import { CircleCheck, TriangleAlert } from '@lucide/vue'
import { isAxiosError } from 'axios'
import { computed, nextTick, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'

import { confirmEmailChange, verifyEmail } from '@/api/email'
import { getProfile } from '@/api/profile'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Spinner } from '@/components/ui/spinner'
import { celebrate } from '@/lib/celebrate'
import { useAuthStore } from '@/stores/auth'

const props = defineProps<{ kind: 'verification' | 'change' }>()

const route = useRoute()
const auth = useAuthStore()

const status = ref<'loading' | 'done' | 'invalid' | 'taken' | 'failed'>('loading')
const doneIcon = ref<HTMLElement | null>(null)

const message = computed(() =>
  status.value === 'failed' ? 'emailConfirmation.failed' : `emailConfirmation.${props.kind}.${status.value}`,
)

const next = computed(() => {
  if (!auth.isAuthenticated) {
    return { to: { name: 'login' }, label: 'emailConfirmation.toLogin' }
  }

  return props.kind === 'change'
    ? { to: { name: 'profile' }, label: 'emailConfirmation.toProfile' }
    : { to: { name: 'trips.index' }, label: 'emailConfirmation.toTrips' }
})

onMounted(async () => {
  const link = {
    user: String(route.params.user),
    hash: String(route.params.hash),
    expires: String(route.query.expires ?? ''),
    signature: String(route.query.signature ?? ''),
  }

  try {
    await (props.kind === 'change' ? confirmEmailChange(link) : verifyEmail(link))
    status.value = 'done'
  } catch (error) {
    const code = isAxiosError(error) ? error.response?.status : undefined
    status.value = code === 403 ? 'invalid' : code === 409 ? 'taken' : 'failed'

    return
  }

  await nextTick()

  if (doneIcon.value) {
    celebrate(doneIcon.value, 'pon')
  }

  if (props.kind === 'change' && auth.isAuthenticated) {
    await getProfile()
      .then(auth.setUser)
      .catch(() => undefined)
  }
})
</script>

<template>
  <Card>
    <CardHeader v-if="status === 'loading'" class="justify-items-center text-center">
      <Spinner class="mb-2 size-6" :aria-label="$t('common.loading')" />
      <CardTitle class="text-xl">{{ $t('emailConfirmation.loading') }}</CardTitle>
    </CardHeader>
    <template v-else>
      <CardHeader>
        <span v-if="status === 'done'" ref="doneIcon" class="mb-2 inline-flex w-fit">
          <CircleCheck class="size-8 text-muted-foreground" aria-hidden="true" />
        </span>
        <TriangleAlert v-else class="mb-2 size-8 text-destructive" aria-hidden="true" />
        <CardTitle class="text-xl">{{ $t(`${message}.title`) }}</CardTitle>
        <CardDescription class="text-pretty">{{ $t(`${message}.description`) }}</CardDescription>
      </CardHeader>
      <CardContent>
        <Button as-child class="w-full">
          <RouterLink :to="next.to">{{ $t(next.label) }}</RouterLink>
        </Button>
      </CardContent>
    </template>
  </Card>
</template>
