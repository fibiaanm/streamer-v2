<template>
  <div class="dark">
    <PageBackground>
      <div class="relative min-h-screen">

        <img
          v-if="imageUrl"
          :src="imageUrl"
          alt=""
          class="absolute inset-0 w-full h-full object-cover"
        />

        <div
          class="flex items-center justify-center min-h-screen px-6
                 w-full max-w-sm mx-auto
                 md:max-w-none md:mx-0 md:absolute md:inset-y-0 md:right-0 md:w-[480px] md:min-h-0 md:px-10"
        >
          <!-- Error: invalid or expired -->
          <div v-if="error" class="glass-auth w-full rounded-2xl p-8 space-y-4">
            <h1 class="text-xl font-bold text-white">Invitación no válida</h1>
            <p class="text-sm text-white/50">
              {{ error === 'expired'
                ? 'Esta invitación ha expirado. Pide al administrador que te envíe una nueva.'
                : 'Este enlace de invitación no es válido o ya fue usado.' }}
            </p>
          </div>

          <!-- Accept form -->
          <div v-else class="glass-auth w-full rounded-2xl p-8 space-y-6">
            <div class="space-y-1">
              <h1 class="text-2xl font-bold text-white">Te han invitado</h1>
              <p class="text-sm text-white/50">
                Únete a <span class="text-white/80 font-medium">{{ invitation!.enterprise_name }}</span>
                como <span class="text-white/80 font-medium">{{ invitation!.role_name }}</span>
              </p>
            </div>

            <form class="space-y-4" @submit.prevent="submit">
              <AppInput
                v-if="!invitation!.user_exists"
                v-model="form.name"
                label="Nombre"
                size="sm"
                :error="errors.name"
              />

              <AppInput
                v-model="form.password"
                type="password"
                :label="invitation!.user_exists ? 'Confirma tu contraseña para unirte' : 'Contraseña'"
                size="sm"
                :error="errors.password"
              />

              <p v-if="apiError" class="text-rose-400 text-xs">{{ apiError }}</p>

              <AppButton type="submit" :loading="loading" class="w-full">
                {{ invitation!.user_exists ? 'Unirme a la empresa' : 'Crear cuenta y unirme' }}
              </AppButton>
            </form>
          </div>

        </div>
      </div>
    </PageBackground>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import PageBackground         from '@/components/PageBackground.vue'
import AppInput               from '@/components/AppInput.vue'
import AppButton              from '@/components/AppButton.vue'
import { useInvitationsApi }  from '@/composables/api/useInvitationsApi'
import { useSession }         from '@/composables/core/useSession'
import { useEnterpriseStore } from '@/stores/enterpriseStore'
import { useApi }             from '@/lib/api'

interface InvitationProps {
  token:           string
  email:           string
  enterprise_name: string
  role_name:       string
  user_exists:     boolean
}

const props = defineProps<{
  invitation?: InvitationProps
  error?:      'invalid' | 'expired'
  imageUrl?:   string
}>()

const { acceptInvitation } = useInvitationsApi()
const { setUser }     = useSession()
const enterpriseStore = useEnterpriseStore()
const api             = useApi()

const loading  = ref(false)
const apiError = ref<string | null>(null)
const errors   = ref<Record<string, string>>({})
const form     = ref({ name: '', password: '' })

async function submit() {
  errors.value   = {}
  apiError.value = null
  loading.value  = true

  const payload = props.invitation!.user_exists
    ? { password: form.value.password }
    : { name: form.value.name, password: form.value.password }

  try {
    const res = await acceptInvitation(props.invitation!.token, payload)
    enterpriseStore.set(res.data.data.enterprise_id)
    const meRes = await api.get<{ data: any }>('/auth/me')
    setUser(meRes.data.data)
    window.location.href = '/app'
  } catch (err: any) {
    const code = err.response?.data?.error?.code
    if (code === 'auth.invalid_credentials') {
      errors.value.password = 'Contraseña incorrecta'
    } else if (code === 'validation.failed') {
      errors.value = err.response.data.error.context?.fields ?? {}
    } else if (code === 'enterprise.member_already_exists') {
      apiError.value = 'Ya eres miembro de esta empresa.'
    } else {
      apiError.value = 'Error del servidor. Inténtalo de nuevo.'
    }
  } finally {
    loading.value = false
  }
}
</script>
