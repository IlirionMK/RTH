<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { toast } from 'vue-sonner'

import TextField from '@/components/TextField.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { FieldGroup } from '@/components/ui/field'
import { Spinner } from '@/components/ui/spinner'
import { type FieldErrors, validationErrors } from '@/lib/api'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const { t } = useI18n()

const form = reactive({ email: '', password: '' })
const errors = ref<FieldErrors>({})
const submitting = ref(false)

async function submit(): Promise<void> {
  submitting.value = true
  errors.value = {}

  try {
    await auth.login(form)
    await router.push({ name: 'trips.index' })
  } catch (error) {
    const fieldErrors = validationErrors(error)

    if (fieldErrors) {
      errors.value = fieldErrors
    } else {
      toast.error(t('common.error'))
    }
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle class="text-xl">{{ $t('login.title') }}</CardTitle>
      <CardDescription>{{ $t('login.description') }}</CardDescription>
    </CardHeader>
    <CardContent>
      <form novalidate @submit.prevent="submit">
        <FieldGroup>
          <TextField
            id="email"
            v-model="form.email"
            type="email"
            autocomplete="email"
            :label="$t('fields.email')"
            :error="errors.email"
          />
          <TextField
            id="password"
            v-model="form.password"
            type="password"
            autocomplete="current-password"
            :label="$t('fields.password')"
            :error="errors.password"
          />
          <Button type="submit" class="w-full" :disabled="submitting">
            <Spinner v-if="submitting" :aria-label="$t('common.loading')" />
            {{ $t('login.submit') }}
          </Button>
        </FieldGroup>
      </form>
    </CardContent>
  </Card>
  <p class="text-center text-sm text-muted-foreground">
    {{ $t('login.noAccount') }}
    <RouterLink :to="{ name: 'register' }" class="font-medium text-foreground underline underline-offset-4">
      {{ $t('login.register') }}
    </RouterLink>
  </p>
</template>
