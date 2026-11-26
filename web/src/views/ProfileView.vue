<script setup lang="ts">
import { Globe, Lock } from '@lucide/vue'
import { computed, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { toast } from 'vue-sonner'

import { updatePassword, updateProfile } from '@/api/profile'
import PageHeader from '@/components/PageHeader.vue'
import TextField from '@/components/TextField.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Field, FieldContent, FieldDescription, FieldLabel, FieldTitle } from '@/components/ui/field'
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group'
import { Spinner } from '@/components/ui/spinner'
import { type FieldErrors, validationErrors } from '@/lib/api'
import { celebrate } from '@/lib/celebrate'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const { t } = useI18n()

function showError(error: unknown, target: { value: FieldErrors }): void {
  const fieldErrors = validationErrors(error)

  if (fieldErrors) {
    target.value = fieldErrors
  } else {
    toast.error(t('common.error'))
  }
}

const basics = reactive({ name: auth.user?.name ?? '', email: auth.user?.email ?? '' })
const basicsErrors = ref<FieldErrors>({})
const savingBasics = ref(false)

const pendingEmail = computed(() =>
  auth.user?.pending_email ? t('profile.emailPending', { email: auth.user.pending_email }) : undefined,
)

async function saveBasics(event: Event): Promise<void> {
  const origin = (event as SubmitEvent).submitter ?? (event.currentTarget as Element)
  savingBasics.value = true
  basicsErrors.value = {}
  const emailChanged = basics.email !== auth.user?.email

  try {
    const user = await updateProfile(emailChanged ? { ...basics } : { name: basics.name })
    auth.setUser(user)
    basics.email = user.email
    toast.success(t(emailChanged && user.pending_email ? 'profile.emailSent' : 'profile.saved'))
    celebrate(origin, 'don')
  } catch (error) {
    showError(error, basicsErrors)
  } finally {
    savingBasics.value = false
  }
}

const visibility = ref(auth.user?.is_public ? 'public' : 'private')
const savingVisibility = ref(false)

async function changeVisibility(value: string): Promise<void> {
  visibility.value = value
  savingVisibility.value = true

  try {
    auth.setUser(await updateProfile({ is_public: value === 'public' }))
    toast.success(t('profile.visibility.saved'))
  } catch {
    visibility.value = auth.user?.is_public ? 'public' : 'private'
    toast.error(t('common.error'))
  } finally {
    savingVisibility.value = false
  }
}

const password = reactive({ current_password: '', password: '', password_confirmation: '' })
const passwordErrors = ref<FieldErrors>({})
const savingPassword = ref(false)

async function savePassword(): Promise<void> {
  savingPassword.value = true
  passwordErrors.value = {}

  try {
    await updatePassword(password)
    Object.assign(password, { current_password: '', password: '', password_confirmation: '' })
    toast.success(t('profile.password.saved'))
  } catch (error) {
    showError(error, passwordErrors)
  } finally {
    savingPassword.value = false
  }
}
</script>

<template>
  <div class="flex max-w-3xl flex-col gap-6">
    <PageHeader :title="$t('profile.title')" :description="$t('profile.description')" />

    <Card>
      <CardHeader>
        <CardTitle>{{ $t('profile.basics') }}</CardTitle>
      </CardHeader>
      <CardContent>
        <form class="flex flex-col gap-4" novalidate @submit.prevent="saveBasics">
          <div class="grid gap-4 md:grid-cols-2">
            <TextField
              id="name"
              v-model="basics.name"
              autocomplete="name"
              :label="$t('fields.name')"
              :error="basicsErrors.name"
            />
            <TextField
              id="email"
              v-model="basics.email"
              type="email"
              autocomplete="email"
              :label="$t('fields.email')"
              :description="pendingEmail"
              :error="basicsErrors.email"
            />
          </div>
          <Button type="submit" class="self-start" :disabled="savingBasics">
            <Spinner v-if="savingBasics" :aria-label="$t('common.loading')" />
            {{ $t('profile.save') }}
          </Button>
        </form>
      </CardContent>
    </Card>

    <Card>
      <CardHeader>
        <CardTitle id="visibility-title">{{ $t('profile.visibility.title') }}</CardTitle>
        <CardDescription>{{ $t('profile.visibility.description') }}</CardDescription>
      </CardHeader>
      <CardContent>
        <RadioGroup
          :model-value="visibility"
          :disabled="savingVisibility"
          aria-labelledby="visibility-title"
          class="grid gap-3 md:grid-cols-2"
          @update:model-value="changeVisibility(String($event))"
        >
          <FieldLabel for="visibility-private">
            <Field orientation="horizontal">
              <RadioGroupItem id="visibility-private" value="private" />
              <FieldContent>
                <FieldTitle>
                  <Lock class="size-4" aria-hidden="true" />
                  {{ $t('profile.visibility.private') }}
                </FieldTitle>
                <FieldDescription>{{ $t('profile.visibility.privateHint') }}</FieldDescription>
              </FieldContent>
            </Field>
          </FieldLabel>
          <FieldLabel for="visibility-public">
            <Field orientation="horizontal">
              <RadioGroupItem id="visibility-public" value="public" />
              <FieldContent>
                <FieldTitle>
                  <Globe class="size-4" aria-hidden="true" />
                  {{ $t('profile.visibility.public') }}
                </FieldTitle>
                <FieldDescription>{{ $t('profile.visibility.publicHint') }}</FieldDescription>
              </FieldContent>
            </Field>
          </FieldLabel>
        </RadioGroup>
      </CardContent>
    </Card>

    <Card>
      <CardHeader>
        <CardTitle>{{ $t('profile.password.title') }}</CardTitle>
      </CardHeader>
      <CardContent>
        <form class="flex flex-col gap-4" novalidate @submit.prevent="savePassword">
          <div class="grid gap-4 md:grid-cols-3">
            <TextField
              id="current_password"
              v-model="password.current_password"
              type="password"
              autocomplete="current-password"
              :label="$t('fields.currentPassword')"
              :error="passwordErrors.current_password"
            />
            <TextField
              id="new_password"
              v-model="password.password"
              type="password"
              autocomplete="new-password"
              :label="$t('fields.newPassword')"
              :error="passwordErrors.password"
            />
            <TextField
              id="new_password_confirmation"
              v-model="password.password_confirmation"
              type="password"
              autocomplete="new-password"
              :label="$t('fields.newPasswordConfirmation')"
              :error="passwordErrors.password_confirmation"
            />
          </div>
          <Button type="submit" class="self-start" :disabled="savingPassword">
            <Spinner v-if="savingPassword" :aria-label="$t('common.loading')" />
            {{ $t('profile.password.submit') }}
          </Button>
        </form>
      </CardContent>
    </Card>
  </div>
</template>
