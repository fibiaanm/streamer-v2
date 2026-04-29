<template>
  <AppModal :is-open="isOpen" @close="emit('close')">
    <template #header>
      <h3 class="text-sm font-semibold text-white/85">Cambiar rol</h3>
      <p class="text-xs text-white/35 mt-0.5">{{ member?.user.name }}</p>
    </template>

    <div class="space-y-1 min-h-[8rem]">
      <template v-if="loading">
        <div v-for="i in 3" :key="i" class="h-11 rounded-xl bg-white/4 animate-pulse" />
      </template>
      <template v-else-if="assignableRoles.length">
        <label
          v-for="role in assignableRoles"
          :key="role.id"
          class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-white/4 transition-colors cursor-pointer"
          :class="selected === role.id ? 'bg-white/6' : ''"
        >
          <input
            type="radio"
            :value="role.id"
            v-model="selected"
            class="accent-sky-500 w-3.5 h-3.5 cursor-pointer"
          />
          <div class="flex-1 min-w-0">
            <p class="text-sm text-white/80">{{ role.name }}</p>
            <p class="text-xs text-white/30">{{ role.permissions.length }} permiso{{ role.permissions.length !== 1 ? 's' : '' }}</p>
          </div>
          <AppBadge v-if="member?.role.id === role.id" variant="neutral" size="sm">actual</AppBadge>
        </label>
      </template>
      <p v-else class="text-xs text-white/25 px-3 py-4">No hay roles que puedas asignar.</p>
    </div>

    <template #footer>
      <AppButton variant="ghost" size="sm" @click="emit('close')">Cancelar</AppButton>
      <AppButton size="sm" :loading="saving" :disabled="!canSave" @click="save">
        Asignar
      </AppButton>
    </template>
  </AppModal>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useWorkspaceSource } from '@/composables/workspace/useWorkspaceSource'
import { useToasts }          from '@/composables/core/useToasts'
import type { WorkspaceMember, WorkspaceRole } from '@/types'
import AppModal  from '@/components/AppModal.vue'
import AppButton from '@/components/AppButton.vue'
import AppBadge  from '@/components/AppBadge.vue'

const props = defineProps<{
  isOpen:      boolean
  workspaceId: string
  member:      WorkspaceMember | null
}>()

const emit = defineEmits<{
  close: []
  saved: [member: WorkspaceMember]
}>()

const source            = useWorkspaceSource()
const { add: addToast } = useToasts()

const assignableRoles = ref<WorkspaceRole[]>([])
const selected        = ref('')
const loading         = ref(false)
const saving          = ref(false)

watch(() => props.isOpen, async (open) => {
  if (!open) return
  selected.value = props.member?.role.id ?? ''
  loading.value  = true
  try {
    assignableRoles.value = await source.listRoles(props.workspaceId, true)
  } catch {
    addToast({ type: 'error', title: 'No se pudieron cargar los roles', duration: 3000 })
  } finally {
    loading.value = false
  }
})

const canSave = computed(() =>
  !!selected.value && selected.value !== props.member?.role.id,
)

async function save() {
  if (!props.member) return
  saving.value = true
  try {
    await source.assignRole(props.workspaceId, props.member.id, selected.value)
    const newRole = assignableRoles.value.find(r => r.id === selected.value)
    const updated: WorkspaceMember = {
      ...props.member,
      role: { id: selected.value, name: newRole?.name ?? '' },
    }
    emit('saved', updated)
    addToast({ type: 'success', title: 'Rol actualizado', duration: 3000 })
    emit('close')
  } catch {
    addToast({ type: 'error', title: 'No se pudo asignar el rol', duration: 5000 })
  } finally {
    saving.value = false
  }
}
</script>
