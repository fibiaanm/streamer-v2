<template>
  <div class="glass-auth w-full rounded-2xl p-8 space-y-6">

    <div class="space-y-1">
      <h1 class="text-2xl font-bold text-white">Crea tu cuenta</h1>
      <p class="text-sm text-white/50">Únete a streamer-v2</p>
    </div>

    <form class="space-y-4" @submit.prevent="submit">
      <AppInput v-model="form.name" label="Nombre" size="sm" :error="errors.name" />
      <AppInput v-model="form.email" type="email" label="Email" size="sm" :error="errors.email" />
      <AppInput v-model="form.password" type="password" label="Contraseña" size="sm" :error="errors.password" />
      <AppInput
        v-model="form.password_confirmation"
        type="password"
        label="Confirmar contraseña"
        size="sm"
        :error="errors.password_confirmation"
      />

      <p v-if="apiError" class="text-rose-400 text-xs">{{ apiError }}</p>

      <AppButton type="submit" :loading="loading" class="w-full">
        Crear cuenta
      </AppButton>
    </form>

    <p class="text-sm text-white/40 text-center">
      ¿Ya tienes cuenta?
      <button
        type="button"
        class="text-brand-400 hover:text-brand-300 transition-colors ml-1 cursor-pointer"
        @click="emit('switch', 'login')"
      >
        Inicia sesión
      </button>
    </p>

  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import AppInput from '@/components/AppInput.vue'
import AppButton from '@/components/AppButton.vue'
import { useApi } from '@/lib/api'
import { useEnterpriseStore } from '@/stores/enterpriseStore'

const emit = defineEmits<{ switch: [mode: string] }>()

const api             = useApi()
const enterpriseStore = useEnterpriseStore()

const form = ref({
  name:                  '',
  email:                 '',
  password:              '',
  password_confirmation: '',
})
const errors   = ref<Record<string, string>>({})
const apiError = ref<string | null>(null)
const loading  = ref(false)

function validate(): boolean {
  const e: Record<string, string> = {}
  if (!form.value.email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
    e.email = 'Ingresa un email válido'
  }
  if (form.value.password.length < 8) {
    e.password = 'Mínimo 8 caracteres'
  }
  if (form.value.password !== form.value.password_confirmation) {
    e.password_confirmation = 'Las contraseñas no coinciden'
  }
  errors.value = e
  return Object.keys(e).length === 0
}

async function submit() {
  if (!validate()) return
  errors.value   = {}
  apiError.value = null
  loading.value  = true
  try {
    const res = await api.post('/auth/register', form.value)
    enterpriseStore.clear()
    window.location.href = '/switch'
  } catch (err: any) {
    const code = err.response?.data?.error?.code
    if (code === 'validation.failed') {
      errors.value = err.response.data.error.context.fields
    } else {
      apiError.value = 'Error del servidor. Inténtalo de nuevo.'
    }
  } finally {
    loading.value = false
  }
}
</script>
