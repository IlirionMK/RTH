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

const form = reactive({ name: '', email: '', password: '', password_confirmation: '' })
const errors = ref<FieldErrors>({})
const submitting = ref(false)

async function submit(): Promise<void> {
  submitting.value = true
  errors.value = {}

  try {
    await auth.register(form)
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
      <CardTitle class="text-xl">{{ $t('register.title') }}</CardTitle>
      <CardDescription>{{ $t('register.description') }}</CardDescription>
    </CardHeader>
    <CardContent>
      <form novalidate @submit.prevent="submit">
        <FieldGroup>
          <TextField
            id="name"
            v-model="form.name"
            autocomplete="name"
            :label="$t('fields.name')"
            :error="errors.name"
          />
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
            autocomplete="new-password"
            :label="$t('fields.password')"
            :description="$t('fields.passwordHint')"
            :error="errors.password"
          />
          <TextField
            id="password_confirmation"
            v-model="form.password_confirmation"
            type="password"
            autocomplete="new-password"
            :label="$t('fields.passwordConfirmation')"
            :error="errors.password_confirmation"
          />
          <Button type="submit" class="w-full" :disabled="submitting">
            <Spinner v-if="submitting" :aria-label="$t('common.loading')" />
            {{ $t('register.submit') }}
          </Button>
        </FieldGroup>
      </form>
    </CardContent>
  </Card>
  <p class="text-center text-sm text-muted-foreground">
    {{ $t('register.hasAccount') }}
    <RouterLink :to="{ name: 'login' }" class="font-medium text-foreground underline underline-offset-4">
      {{ $t('register.login') }}
    </RouterLink>
  </p>
</template>
