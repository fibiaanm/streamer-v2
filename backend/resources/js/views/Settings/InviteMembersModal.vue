<template>
  <AppModal :is-open="isOpen" max-width="md" @close="handleClose">
    <template #header>
      <div>
        <p class="text-sm font-semibold text-white">Invitar personas</p>
        <p class="text-xs text-white/40 mt-0.5">
          Se unirán con el rol
          <span class="text-white/60">{{ selectedRole?.name ?? '…' }}</span>
        </p>
      </div>
    </template>

    <div class="space-y-4">
      <div>
        <label class="block text-xs text-white/40 mb-2">Correos electrónicos</label>
        <EmailTagInput v-model="emails" />
      </div>

      <div>
        <label class="block text-xs text-white/40 mb-2">Rol</label>
        <AppSelect v-model="selected" :options="roleOptions" :disabled="roleOptions.length === 0" />
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
        :disabled="validEmails.length === 0 || !selected"
        @click="submit"
      >
        Enviar {{ validEmails.length > 0 ? `(${validEmails.length})` : '' }}
      </AppButton>
    </template>
  </AppModal>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import AppModal      from '@/components/AppModal.vue'
import AppButton     from '@/components/AppButton.vue'
import AppSelect     from '@/components/AppSelect.vue'
import EmailTagInput from '@/components/EmailTagInput.vue'
import { useMembersApi }  from '@/composables/api/useMembersApi'
import { useRolesApi }    from '@/composables/api/useRolesApi'
import { usePermissions } from '@/composables/core/usePermissions'
import type { Role }      from '@/composables/api/useRolesApi'
import type { SelectOption } from '@/components/AppSelect.vue'

const props = defineProps<{ isOpen: boolean }>()

const emit = defineEmits<{
  close:   []
  invited: []
}>()

const { invite }      = useMembersApi()
const { listRoles }   = useRolesApi()
const { permissions } = usePermissions()

const emails       = ref<string[]>([])
const loading      = ref(false)
const errorMessage = ref('')
const allRoles     = ref<Role[]>([])
const selected     = ref('')

const isValidEmail = (email: string) =>
  /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.trim())

const validEmails = computed(() => emails.value.filter(isValidEmail))

const assignableRoles = computed(() => {
  const myPerms = new Set(permissions.value)
  return allRoles.value.filter(r => !r.is_owner && r.permissions.every(p => myPerms.has(p)))
})

const roleOptions = computed<SelectOption[]>(() =>
  assignableRoles.value.map(r => ({ value: r.id, label: r.name })),
)

const selectedRole = computed(() =>
  assignableRoles.value.find(r => r.id === selected.value) ?? null,
)

watch(() => props.isOpen, async (open) => {
  if (!open) return
  const res  = await listRoles()
  allRoles.value = res.data.data
  const member = assignableRoles.value.find(r => r.is_global && r.name === 'member')
  selected.value = member?.id ?? assignableRoles.value[0]?.id ?? ''
})

const reset = () => {
  emails.value       = []
  errorMessage.value = ''
  selected.value     = ''
  allRoles.value     = []
}

const handleClose = () => {
  if (loading.value) return
  reset()
  emit('close')
}

const submit = async () => {
  if (validEmails.value.length === 0 || !selected.value) return

  loading.value      = true
  errorMessage.value = ''

  try {
    await invite(validEmails.value, selected.value)
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
