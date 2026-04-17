<template>
  <div class="h-full flex flex-col p-8 gap-6 overflow-y-auto">

    <div class="space-y-1">
      <h2 class="text-sm font-semibold text-white/70">Empresa</h2>
      <p class="text-xs text-white/30">Información general de tu organización</p>
    </div>

    <div class="max-w-sm space-y-4">
      <AppInput
        v-model="name"
        label="Nombre"
        :readonly="!canEditSettings"
      />

      <div v-if="canEditSettings" class="flex items-center gap-3">
        <AppButton
          size="sm"
          :loading="saving"
          :disabled="!canSave"
          @click="save"
        >
          Guardar
        </AppButton>
        <p v-if="error" class="text-xs text-rose-400">{{ error }}</p>
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useSession } from '@/composables/core/useSession'
import { usePermissions } from '@/composables/core/usePermissions'
import { useApi } from '@/lib/api'
import AppInput  from '@/components/AppInput.vue'
import AppButton from '@/components/AppButton.vue'

const { user }            = useSession()
const { canEditSettings } = usePermissions()
const api                 = useApi()

const original = ref(user.value?.enterprise.name ?? '')
const name     = ref(original.value)
const saving   = ref(false)
const error    = ref<string | null>(null)

const canSave = computed(() => name.value.trim() !== '' && name.value !== original.value)

watch(() => user.value?.enterprise.name, (val) => {
  if (val) { original.value = val; name.value = val }
})

async function save() {
  saving.value = true
  error.value  = null
  try {
    await api.patch('/enterprises/current', { name: name.value })
    original.value = name.value
    if (user.value) user.value.enterprise.name = name.value
  } catch {
    error.value = 'No se pudo guardar. Intenta de nuevo.'
  } finally {
    saving.value = false
  }
}
</script>
