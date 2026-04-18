<template>
  <AppModal :is-open="isOpen" @close="emit('close')">
    <template #header>
      <h3 class="text-sm font-semibold text-white/85">Cambiar rol</h3>
      <p class="text-xs text-white/35 mt-0.5">{{ member?.user.name }}</p>
    </template>

    <div class="space-y-1 min-h-[8rem]">
      <template v-if="assignableRoles.length">
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
import { useRolesApi }      from '@/composables/api/useRolesApi'
import { useMembersApi }    from '@/composables/api/useMembersApi'
import { usePermissions }   from '@/composables/core/usePermissions'
import { useToasts }        from '@/composables/core/useToasts'
import type { Role } from '@/composables/api/useRolesApi'
import type { Member } from '@/composables/api/useMembersApi'
import AppModal  from '@/components/AppModal.vue'
import AppButton from '@/components/AppButton.vue'
import AppBadge  from '@/components/AppBadge.vue'

const props = defineProps<{
  isOpen: boolean
  member: Member | null
}>()

const emit = defineEmits<{
  close: []
  saved: [member: Member]
}>()

const { listRoles }  = useRolesApi()
const { assignRole } = useMembersApi()
const { permissions }    = usePermissions()
const { add: addToast } = useToasts()

const allRoles = ref<Role[]>([])
const selected = ref<string>('')
const saving   = ref(false)

watch(() => props.isOpen, async (open) => {
  if (!open) return
  selected.value = props.member?.role.id ?? ''
  const res = await listRoles()
  allRoles.value = res.data.data
})

const assignableRoles = computed(() => {
  const myPermissions = new Set(permissions.value)
  return allRoles.value.filter(r =>
    !r.is_owner && r.permissions.every(p => myPermissions.has(p)),
  )
})

const canSave = computed(() =>
  !!selected.value && selected.value !== props.member?.role.id,
)

async function save() {
  if (!props.member) return
  saving.value = true
  try {
    const res  = await assignRole(props.member.id, selected.value)
    const saved = res.data.data as Member
    emit('saved', saved)
    addToast({ type: 'success', title: 'Rol actualizado', duration: 3000 })
    emit('close')
  } catch {
    addToast({ type: 'error', title: 'No se pudo asignar el rol', duration: 5000 })
  } finally {
    saving.value = false
  }
}
</script>
