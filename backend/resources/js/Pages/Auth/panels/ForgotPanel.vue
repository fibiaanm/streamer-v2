<template>
  <div class="glass w-full rounded-2xl p-8 space-y-6">

    <div class="space-y-1">
      <h1 class="text-2xl font-bold text-white">Recuperar acceso</h1>
      <p class="text-sm text-white/50">Te enviaremos un enlace a tu email</p>
    </div>

    <form class="space-y-4" @submit.prevent="submit">
      <AppInput v-model="form.email" type="email" label="Email" size="sm" :error="errors.email" />

      <p v-if="apiError" class="text-rose-400 text-xs">{{ apiError }}</p>
      <p v-if="success" class="text-emerald-400 text-xs">{{ success }}</p>

      <AppButton type="submit" :loading="loading" :disabled="!!success" class="w-full">
        Enviar enlace
      </AppButton>
    </form>

    <p class="text-center">
      <button
        type="button"
        class="text-sm text-white/40 hover:text-white/60 transition-colors cursor-pointer"
        @click="emit('switch', 'login')"
      >
        ← Volver al inicio de sesión
      </button>
    </p>

  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import AppInput from '@/components/AppInput.vue'
import AppButton from '@/components/AppButton.vue'
import { useApi } from '@/lib/api'

const emit = defineEmits<{ switch: [mode: string] }>()

const api = useApi()

const form     = ref({ email: '' })
const errors   = ref<Record<string, string>>({})
const apiError = ref<string | null>(null)
const success  = ref<string | null>(null)
const loading  = ref(false)

async function submit() {
  errors.value   = {}
  apiError.value = null
  loading.value  = true
  try {
    await api.post('/auth/forgot-password', form.value)
    success.value = 'Revisa tu bandeja. Si el email existe, recibirás un enlace en breve.'
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
