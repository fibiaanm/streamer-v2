<template>
  <AppModal :is-open="isOpen" @close="emit('close')">
    <template #header>
      <h3 class="text-sm font-semibold text-white/85">
        {{ role ? 'Editar rol' : 'Nuevo rol' }}
      </h3>
    </template>

    <div class="space-y-5">
      <AppInput v-model="form.name" label="Nombre del rol" size="sm" />

      <div class="space-y-2">
        <p class="text-xs text-white/40">Permisos</p>
        <div class="space-y-1 max-h-64 overflow-y-auto pr-1">
          <label
            v-for="perm in availablePermissions"
            :key="perm"
            class="flex items-center gap-3 px-3 py-2 rounded-xl hover:bg-white/4 transition-colors cursor-pointer"
          >
            <input
              type="checkbox"
              :checked="form.permissions.includes(perm)"
              class="accent-sky-500 w-3.5 h-3.5 cursor-pointer"
              @change="togglePermission(perm)"
            />
            <span class="text-xs text-white/60 font-mono">{{ perm }}</span>
          </label>
        </div>
      </div>
    </div>

    <template #footer>
      <AppButton variant="ghost" size="sm" @click="emit('close')">Cancelar</AppButton>
      <AppButton size="sm" :loading="saving" :disabled="!canSave" @click="save">
        {{ role ? 'Guardar' : 'Crear' }}
      </AppButton>
    </template>
  </AppModal>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue'
import { useRolesApi } from '@/composables/api/useRolesApi'
import { useToasts } from '@/composables/core/useToasts'
import type { Role } from '@/composables/api/useRolesApi'
import AppModal  from '@/components/AppModal.vue'
import AppInput  from '@/components/AppInput.vue'
import AppButton from '@/components/AppButton.vue'

const props = defineProps<{
  isOpen:               boolean
  role:                 Role | null
  availablePermissions: string[]
}>()

const emit = defineEmits<{
  close:  []
  saved:  [role: Role]
  deleted: [id: string]
}>()

const { createRole, updateRole } = useRolesApi()
const { add: addToast }          = useToasts()

const saving = ref(false)

const form = reactive({ name: '', permissions: [] as string[] })

watch(() => props.isOpen, (open) => {
  if (!open) return
  form.name        = props.role?.name ?? ''
  form.permissions = props.role ? [...props.role.permissions] : []
})

const canSave = computed(() => form.name.trim().length > 0)

const togglePermission = (perm: string) => {
  const idx = form.permissions.indexOf(perm)
  if (idx === -1) form.permissions.push(perm)
  else form.permissions.splice(idx, 1)
}

async function save() {
  saving.value = true
  try {
    const res = props.role
      ? await updateRole(props.role.id, { name: form.name, permissions: form.permissions })
      : await createRole(form.name, form.permissions)

    emit('saved', res.data.data)
    addToast({ type: 'success', title: props.role ? 'Rol actualizado' : 'Rol creado', duration: 3000 })
    emit('close')
  } catch {
    addToast({ type: 'error', title: 'No se pudo guardar', message: 'Intenta de nuevo.', duration: 5000 })
  } finally {
    saving.value = false
  }
}
</script>

