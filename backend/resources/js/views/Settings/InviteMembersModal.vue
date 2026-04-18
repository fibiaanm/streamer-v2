<template>
  <AppModal :is-open="isOpen" max-width="md" @close="handleClose">
    <template #header>
      <div>
        <p class="text-sm font-semibold text-white">Invitar personas</p>
        <p class="text-xs text-white/40 mt-0.5">Se unirán con el rol <span class="text-white/60">member</span></p>
      </div>
    </template>

    <div class="space-y-4">
      <div>
        <label class="block text-xs text-white/40 mb-2">Correos electrónicos</label>
        <EmailTagInput v-model="emails" />
      </div>

      <p v-if="errorMessage" class="text-xs text-rose-400">{{ errorMessage }}</p>
    </div>

    <template #footer>
      <AppButton variant="secondary" size="sm" :disabled="loading" @click="handleClose">
        Cancelar
      </AppButton>
      <AppButton
        size="sm"
        :loading="loading"
        :disabled="validEmails.length === 0"
        @click="submit"
      >
        Enviar {{ validEmails.length > 0 ? `(${validEmails.length})` : '' }}
      </AppButton>
    </template>
  </AppModal>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import AppModal      from '@/components/AppModal.vue'
import AppButton     from '@/components/AppButton.vue'
import EmailTagInput from '@/components/EmailTagInput.vue'
import { useMembersApi } from '@/composables/api/useMembersApi'

const props = defineProps<{ isOpen: boolean }>()

const emit = defineEmits<{
  close:   []
  invited: []
}>()

const { invite } = useMembersApi()

const emails       = ref<string[]>([])
const loading      = ref(false)
const errorMessage = ref('')

const isValidEmail = (email: string) =>
  /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.trim())

const validEmails = computed(() => emails.value.filter(isValidEmail))

const reset = () => {
  emails.value       = []
  errorMessage.value = ''
}

// Cierre manual (botón Cancelar / X): bloqueado mientras carga
const handleClose = () => {
  if (loading.value) return
  reset()
  emit('close')
}

const submit = async () => {
  if (validEmails.value.length === 0) return

  loading.value      = true
  errorMessage.value = ''

  try {
    await invite(validEmails.value)
    emit('invited')
    reset()
    emit('close')
  } catch {
    errorMessage.value = 'No se pudieron enviar algunas invitaciones. Inténtalo de nuevo.'
  } finally {
    loading.value = false
  }
}
</script>
