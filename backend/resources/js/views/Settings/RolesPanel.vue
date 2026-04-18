<template>
  <div class="h-full flex flex-col p-8 gap-6 overflow-y-auto">

    <!-- Header -->
    <div class="flex items-center justify-between">
      <p class="text-xs text-white/30">{{ customRoles.length }} rol{{ customRoles.length !== 1 ? 'es' : '' }} personalizados</p>
      <AppButton v-if="canManageRoles" size="sm" icon="ui/plus" @click="openCreate">
        Nuevo rol
      </AppButton>
    </div>

    <!-- Custom roles -->
    <div v-if="customRoles.length" class="space-y-1">
      <div
        v-for="role in customRoles"
        :key="role.id"
        class="group flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-white/4 transition-colors"
      >
        <div class="flex-1 min-w-0 space-y-1">
          <p class="text-sm text-white/80">{{ role.name }}</p>
          <p class="text-xs text-white/30 truncate">{{ role.permissions.length }} permiso{{ role.permissions.length !== 1 ? 's' : '' }}</p>
        </div>

        <div v-if="canManageRoles" class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
          <button
            class="p-1.5 rounded-lg text-white/30 hover:text-white/70 hover:bg-white/8 transition-colors cursor-pointer"
            @click="openEdit(role)"
          >
            <AppIcon name="ui/pencil" size="sm" />
          </button>
          <button
            class="p-1.5 rounded-lg text-white/30 hover:text-rose-400 hover:bg-white/8 transition-colors cursor-pointer"
            @click="confirmDelete(role)"
          >
            <AppIcon name="ui/x" size="sm" />
          </button>
        </div>
      </div>
    </div>

    <div v-else class="py-4">
      <p class="text-xs text-white/20">No hay roles personalizados todavía.</p>
    </div>

    <!-- Base roles -->
    <div class="border-t border-white/8 pt-4 space-y-1">
      <p class="text-xs text-white/25 mb-3">Roles del sistema</p>
      <div
        v-for="role in baseRoles"
        :key="role.id"
        class="flex items-center gap-3 px-3 py-2.5 rounded-xl"
      >
        <div class="flex-1 min-w-0 space-y-1">
          <p class="text-sm text-white/40">{{ role.name }}</p>
          <p class="text-xs text-white/20 truncate">{{ role.permissions.length }} permiso{{ role.permissions.length !== 1 ? 's' : '' }}</p>
        </div>
        <AppBadge variant="neutral" size="sm">sistema</AppBadge>
      </div>
    </div>

  </div>

  <RoleEditModal
    :is-open="modalOpen"
    :role="editingRole"
    :available-permissions="availablePermissions"
    @close="modalOpen = false"
    @saved="onSaved"
  />
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { usePermissions } from '@/composables/core/usePermissions'
import { useRolesApi } from '@/composables/api/useRolesApi'
import { useToasts } from '@/composables/core/useToasts'
import type { Role } from '@/composables/api/useRolesApi'
import AppButton    from '@/components/AppButton.vue'
import AppBadge     from '@/components/AppBadge.vue'
import AppIcon      from '@/components/AppIcon.vue'
import RoleEditModal from './RoleEditModal.vue'

const { canManageRoles }                       = usePermissions()
const { listRoles, listPermissions, deleteRole } = useRolesApi()
const { add: addToast }                        = useToasts()

const roles                = ref<Role[]>([])
const availablePermissions = ref<string[]>([])
const modalOpen            = ref(false)
const editingRole          = ref<Role | null>(null)

const customRoles = computed(() => roles.value.filter(r => !r.is_global))
const baseRoles   = computed(() => roles.value.filter(r => r.is_global))

onMounted(async () => {
  const [rolesRes, permsRes] = await Promise.all([listRoles(), listPermissions()])
  roles.value                = rolesRes.data.data
  availablePermissions.value = permsRes.data.data
})

const openCreate = () => { editingRole.value = null; modalOpen.value = true }
const openEdit   = (role: Role) => { editingRole.value = role; modalOpen.value = true }

const onSaved = (saved: Role) => {
  const idx = roles.value.findIndex(r => r.id === saved.id)
  if (idx !== -1) roles.value[idx] = saved
  else roles.value.push(saved)
}

async function confirmDelete(role: Role) {
  try {
    await deleteRole(role.id)
    roles.value = roles.value.filter(r => r.id !== role.id)
    addToast({ type: 'success', title: 'Rol eliminado', duration: 3000 })
  } catch {
    addToast({ type: 'error', title: 'No se pudo eliminar', message: 'El rol puede tener miembros asignados.', duration: 5000 })
  }
}
</script>
