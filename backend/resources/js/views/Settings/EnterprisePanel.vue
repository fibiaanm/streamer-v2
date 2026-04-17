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
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useSession } from '@/composables/core/useSession'
import { usePermissions } from '@/composables/core/usePermissions'
import { useToasts } from '@/composables/core/useToasts'
import { useApi } from '@/lib/api'
import AppInput  from '@/components/AppInput.vue'
import AppButton from '@/components/AppButton.vue'

const { user }            = useSession()
const { canEditSettings } = usePermissions()
const { add: addToast }   = useToasts()
const api                 = useApi()

const original = ref(user.value?.enterprise.name ?? '')
const name     = ref(original.value)
const saving   = ref(false)

const canSave = computed(() => name.value.trim() !== '' && name.value !== original.value)

watch(() => user.value?.enterprise.name, (val) => {
  if (val) { original.value = val; name.value = val }
})

async function save() {
  saving.value = true
  try {
    await api.patch('/enterprises/current', { name: name.value })
    original.value = name.value
    if (user.value) user.value.enterprise.name = name.value
    addToast({ type: 'success', title: 'Cambios guardados', duration: 3000 })
  } catch {
    addToast({ type: 'error', title: 'No se pudo guardar', message: 'Intenta de nuevo.', duration: 5000 })
  } finally {
    saving.value = false
  }
}
</script>
