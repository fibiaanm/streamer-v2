<template>
  <div class="glass-auth w-full rounded-2xl p-8 space-y-6">

    <div class="space-y-1">
      <h1 class="text-2xl font-bold text-white">Bienvenido de vuelta</h1>
      <p class="text-sm text-white/50">Inicia sesión en tu cuenta</p>
    </div>

    <form class="space-y-4" @submit.prevent="submit">
      <AppInput v-model="form.email" type="email" label="Email" size="sm" :error="errors.email" />
      <AppInput v-model="form.password" type="password" label="Contraseña" size="sm" :error="errors.password" />

      <div class="flex justify-end">
        <button
          type="button"
          class="text-xs text-white/35 hover:text-white/60 transition-colors cursor-pointer"
          @click="emit('switch', 'forgot')"
        >
          ¿Olvidaste tu contraseña?
        </button>
      </div>

      <p v-if="apiError" class="text-rose-400 text-xs">{{ apiError }}</p>

      <AppButton type="submit" :loading="loading" class="w-full">
        Iniciar sesión
      </AppButton>
    </form>

    <p class="text-sm text-white/40 text-center">
      ¿Sin cuenta?
      <button
        type="button"
        class="text-brand-400 hover:text-brand-300 transition-colors ml-1 cursor-pointer"
        @click="emit('switch', 'signup')"
      >
        Regístrate
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

const form     = ref({ email: '', password: '' })
const errors   = ref<Record<string, string>>({})
const apiError = ref<string | null>(null)
const loading  = ref(false)

async function submit() {
  errors.value   = {}
  apiError.value = null
  loading.value  = true
  try {
    const res = await api.post('/auth/login', form.value)
    enterpriseStore.clear()
    window.location.href = '/switch'
  } catch (err: any) {
    const code = err.response?.data?.error?.code
    if (code === 'auth.invalid_credentials') {
      apiError.value = 'Email o contraseña incorrectos'
    } else if (code === 'validation.failed') {
      errors.value = err.response.data.error.context.fields
    } else {
      apiError.value = 'Error del servidor. Inténtalo de nuevo.'
    }
  } finally {
    loading.value = false
  }
}
</script>
