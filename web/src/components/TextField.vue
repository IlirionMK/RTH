<script setup lang="ts">
import { computed } from 'vue'

import { Field, FieldDescription, FieldError, FieldLabel } from '@/components/ui/field'
import { Input } from '@/components/ui/input'

const props = defineProps<{
  id: string
  label: string
  type?: string
  autocomplete?: string
  description?: string
  error?: string
}>()

const model = defineModel<string>({ required: true })

const describedBy = computed(() => {
  if (props.error) {
    return `${props.id}-error`
  }

  return props.description ? `${props.id}-description` : undefined
})
</script>

<template>
  <Field :data-invalid="error ? true : undefined">
    <FieldLabel :for="id">{{ label }}</FieldLabel>
    <Input
      :id="id"
      :model-value="model"
      :type="type ?? 'text'"
      :autocomplete="autocomplete"
      :aria-invalid="error ? true : undefined"
      :aria-describedby="describedBy"
      @update:model-value="model = String($event)"
    />
    <FieldError v-if="error" :id="`${id}-error`">{{ error }}</FieldError>
    <FieldDescription v-else-if="description" :id="`${id}-description`">{{ description }}</FieldDescription>
  </Field>
</template>
