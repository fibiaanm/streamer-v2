<template>
  <AppModal :is-open="isOpen" max-width="2xl" @close="onClose">
    <template #header>
      <div class="flex items-center gap-2.5">
        <div class="w-7 h-7 rounded-lg bg-white/6 flex items-center justify-center">
          <AppIcon name="ui/settings" size="sm" class="text-white/40" />
        </div>
        <div class="flex items-baseline gap-2">
          <span class="text-sm font-semibold text-white/80">Configuración</span>
          <span class="text-xs text-white/30">{{ workspace?.name }}</span>
        </div>
      </div>
    </template>

    <div class="-mx-5 -my-5 flex" style="min-height: 420px;">

      <!-- Left nav -->
      <div class="w-36 shrink-0 border-r border-white/6 py-3 flex flex-col gap-0.5">
        <button
          v-for="s in sections"
          :key="s.id"
          class="mx-2 flex items-center gap-2 px-2.5 py-2 rounded-lg text-xs transition-colors cursor-pointer"
          :class="activeSection === s.id
            ? 'bg-white/8 text-white/80'
            : 'text-white/35 hover:text-white/55 hover:bg-white/4'"
          @click="activeSection = s.id"
        >
          <AppIcon :name="s.icon" size="xs" class="shrink-0" />
          {{ s.label }}
        </button>
      </div>

      <!-- Right content -->
      <div class="flex-1 overflow-y-auto p-5">

        <!-- General -->
        <template v-if="activeSection === 'general'">
          <div class="flex flex-col gap-5 max-w-xs">
            <p class="text-[11px] font-semibold uppercase tracking-widest text-white/30">General</p>
            <AppInput v-model="editName" label="Nombre de la carpeta" :disabled="!can('workspace.edit')" />
            <div v-if="can('workspace.edit')" class="flex gap-2">
              <AppButton
                variant="primary"
                size="sm"
                :disabled="!editName.trim() || editName === workspace?.name"
                @click="onSaveName"
              >
                Guardar
              </AppButton>
              <AppButton variant="ghost" size="sm" @click="editName = workspace?.name ?? ''">
                Descartar
              </AppButton>
            </div>
          </div>
        </template>

        <!-- Members -->
        <template v-else-if="activeSection === 'members'">
          <div class="flex flex-col gap-4">

            <div class="flex items-center justify-between">
              <p class="text-[11px] font-semibold uppercase tracking-widest text-white/30">
                Miembros
                <span v-if="!membersLoading" class="normal-case font-normal text-white/20">
                  {{ members.length }}
                </span>
              </p>
            </div>

            <template v-if="membersLoading">
              <div v-for="i in 3" :key="i" class="h-11 rounded-xl bg-white/4 animate-pulse" />
            </template>

            <div v-else class="space-y-0.5">
              <MemberRow
                v-for="member in members"
                :key="member.id"
                :name="member.user.name"
                :sub="member.user.email"
                class="group"
              >
                <template #avatar>
                  <div class="w-8 h-8 rounded-full bg-white/8 flex items-center justify-center text-[10px] font-bold text-white/50 uppercase shrink-0">
                    {{ initials(member.user.name) }}
                  </div>
                </template>
                <template #badge>
                  <AppBadge :variant="roleBadgeVariant(member.role.name)" size="sm">
                    {{ member.role.name }}
                  </AppBadge>
                </template>
                <template #actions>
                  <AppDropdown
                    v-if="canActOnMember(member)"
                    align="right"
                  >
                    <template #trigger>
                      <button class="opacity-0 group-hover:opacity-100 transition-opacity w-6 h-6 flex items-center justify-center rounded-full hover:bg-white/8 text-white/40 hover:text-white/70 cursor-pointer">
                        <AppIcon name="ui/more-horizontal" size="sm" />
                      </button>
                    </template>
                    <AppDropdownItem
                      v-if="can('workspace.members.change_role') && member.role.name.toLowerCase() !== 'owner'"
                      icon="ui/shield"
                      @click="openAssignRole(member)"
                    >
                      Cambiar rol
                    </AppDropdownItem>
                    <AppDropdownItem
                      v-if="can('workspace.members.delete') && member.role.name.toLowerCase() !== 'owner'"
                      icon="ui/user-x"
                      variant="danger"
                      @click="removeMember(member.id)"
                    >
                      Eliminar
                    </AppDropdownItem>
                  </AppDropdown>
                  <div v-else class="w-6 shrink-0" />
                </template>
              </MemberRow>
            </div>

            <div v-if="can('workspace.members.add')" class="border-t border-white/6 pt-4">
              <p class="text-[11px] font-semibold uppercase tracking-widest text-white/30 mb-3">Invitar</p>

              <div v-if="!showInviteForm">
                <AppButton variant="secondary" size="sm" icon="ui/plus" @click="openInviteForm">
                  Añadir miembro
                </AppButton>
              </div>

              <div v-else class="flex flex-col gap-3">
                <EmailTagInput v-model="inviteEmails" label="Correos electrónicos" />
                <AppSelect v-model="inviteRole" :options="assignableRoleOptions" label="Rol" />
                <div class="flex gap-2">
                  <AppButton variant="ghost" size="sm" @click="cancelInvite">Cancelar</AppButton>
                  <AppButton
                    variant="primary"
                    size="sm"
                    :loading="inviting"
                    :disabled="inviteEmails.length === 0 || !inviteRole"
                    @click="sendInvite"
                  >
                    Invitar
                  </AppButton>
                </div>
              </div>
            </div>

          </div>
        </template>

        <!-- Roles -->
        <template v-else-if="activeSection === 'roles'">
          <div class="flex flex-col gap-4">

            <div class="flex items-center justify-between">
              <p class="text-[11px] font-semibold uppercase tracking-widest text-white/30">
                {{ customRoles.length }} rol{{ customRoles.length !== 1 ? 'es' : '' }} personalizados
              </p>
              <AppButton v-if="can('workspace.roles.add')" size="sm" icon="ui/plus" @click="openCreateRole">
                Nuevo rol
              </AppButton>
            </div>

            <template v-if="rolesLoading">
              <div v-for="i in 3" :key="i" class="h-11 rounded-xl bg-white/4 animate-pulse" />
            </template>

            <template v-else>
              <div v-if="customRoles.length" class="space-y-1">
                <div
                  v-for="role in customRoles"
                  :key="role.id"
                  class="group flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-white/4 transition-colors"
                >
                  <div class="flex-1 min-w-0 space-y-1">
                    <p class="text-sm text-white/80">{{ role.name }}</p>
                    <p class="text-xs text-white/30">{{ role.permissions.length }} permiso{{ role.permissions.length !== 1 ? 's' : '' }}</p>
                  </div>
                  <div v-if="can('workspace.roles.edit') || can('workspace.roles.delete')" class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button
                      v-if="can('workspace.roles.edit')"
                      class="p-1.5 rounded-lg text-white/30 hover:text-white/70 hover:bg-white/8 transition-colors cursor-pointer"
                      @click="openEditRole(role)"
                    >
                      <AppIcon name="ui/pencil" size="sm" />
                    </button>
                    <button
                      v-if="can('workspace.roles.delete')"
                      class="p-1.5 rounded-lg text-white/30 hover:text-rose-400 hover:bg-white/8 transition-colors cursor-pointer"
                      @click="deleteRole(role)"
                    >
                      <AppIcon name="ui/x" size="sm" />
                    </button>
                  </div>
                </div>
              </div>
              <p v-else class="text-xs text-white/20 py-2">No hay roles personalizados todavía.</p>

              <div class="border-t border-white/8 pt-4 space-y-1">
                <p class="text-xs text-white/25 mb-3">Roles del sistema</p>
                <div
                  v-for="role in baseRoles"
                  :key="role.id"
                  class="flex items-center gap-3 px-3 py-2.5 rounded-xl"
                >
                  <div class="flex-1 min-w-0 space-y-1">
                    <p class="text-sm text-white/40">{{ role.name }}</p>
                    <p class="text-xs text-white/20">{{ role.permissions.length }} permiso{{ role.permissions.length !== 1 ? 's' : '' }}</p>
                  </div>
                  <AppBadge variant="neutral" size="sm">sistema</AppBadge>
                </div>
              </div>
            </template>

          </div>
        </template>

        <!-- Danger -->
        <template v-else-if="activeSection === 'danger'">
          <div class="flex flex-col gap-4">
            <p class="text-[11px] font-semibold uppercase tracking-widest text-white/30">Zona de peligro</p>

            <div class="rounded-xl border border-rose-500/20 bg-rose-500/5 p-4 flex flex-col gap-3">
              <div>
                <p class="text-xs font-semibold text-white/70">Archivar carpeta</p>
                <p class="text-[11px] text-white/35 mt-0.5 leading-relaxed">
                  La carpeta y su contenido quedarán archivados. Podrás restaurarlos más adelante.
                </p>
              </div>

              <template v-if="!archiveConfirm">
                <AppButton variant="danger" size="sm" @click="archiveConfirm = true">Archivar</AppButton>
              </template>

              <template v-else>
                <div class="flex flex-col gap-2.5 border-t border-rose-500/15 pt-3">
                  <p class="text-[11px] text-white/50">
                    ¿Estás seguro? Esta acción archivará
                    <span class="font-semibold text-white/70">{{ workspace?.name }}</span>.
                  </p>
                  <div class="flex gap-2">
                    <AppButton variant="ghost" size="xs" @click="archiveConfirm = false">Cancelar</AppButton>
                    <AppButton variant="danger" size="xs" @click="onArchive">Sí, archivar</AppButton>
                  </div>
                </div>
              </template>
            </div>
          </div>
        </template>

      </div>
    </div>
  </AppModal>

  <WorkspaceAssignRoleModal
    :is-open="assignRoleOpen"
    :workspace-id="workspace?.id ?? ''"
    :member="assignRoleTarget"
    @close="assignRoleOpen = false"
    @saved="onRoleAssigned"
  />

  <WorkspaceRoleEditModal
    :is-open="roleEditOpen"
    :workspace-id="workspace?.id ?? ''"
    :role="editingRole"
    @close="roleEditOpen = false"
    @saved="onRoleSaved"
  />
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import AppModal        from '@/components/AppModal.vue'
import AppInput        from '@/components/AppInput.vue'
import AppButton       from '@/components/AppButton.vue'
import AppIcon         from '@/components/AppIcon.vue'
import AppBadge        from '@/components/AppBadge.vue'
import AppSelect       from '@/components/AppSelect.vue'
import AppDropdown     from '@/components/AppDropdown.vue'
import AppDropdownItem from '@/components/AppDropdownItem.vue'
import EmailTagInput   from '@/components/EmailTagInput.vue'
import MemberRow       from '@/views/Settings/MemberRow.vue'
import WorkspaceAssignRoleModal from './WorkspaceAssignRoleModal.vue'
import WorkspaceRoleEditModal   from './WorkspaceRoleEditModal.vue'
import { useWorkspaceSource } from '@/composables/workspace/useWorkspaceSource'
import { useToasts }          from '@/composables/core/useToasts'
import type { WorkspaceMember, WorkspaceRole } from '@/types'

const props = defineProps<{
  isOpen:        boolean
  workspace?:    { id: string; name: string } | null
  capabilities?: string[]
}>()

const emit = defineEmits<{
  close:    []
  renamed:  [id: string, name: string]
  archived: [id: string]
}>()

const can = (p: string) => props.capabilities?.includes(p) ?? false

const source            = useWorkspaceSource()
const { add: addToast } = useToasts()

// ── Sections ──────────────────────────────────────────────────────────────────

const sections = computed(() => [
  { id: 'general', label: 'General',  icon: 'ui/settings' },
  ...(can('workspace.members.view')
    ? [{ id: 'members', label: 'Miembros', icon: 'ui/users' }]
    : []),
  ...(can('workspace.roles.view')
    ? [{ id: 'roles',   label: 'Roles',    icon: 'ui/shield' }]
    : []),
  ...(can('workspace.delete')
    ? [{ id: 'danger',  label: 'Peligro',  icon: 'ui/x' }]
    : []),
])

const activeSection = ref('general')

// ── Reset on open ─────────────────────────────────────────────────────────────

watch(() => props.isOpen, (open) => {
  if (!open) return
  activeSection.value  = 'general'
  archiveConfirm.value = false
  showInviteForm.value = false
  inviteEmails.value   = []
  inviteRole.value     = ''
})

// ── General ───────────────────────────────────────────────────────────────────

const editName = ref('')

watch(() => props.workspace, (ws) => {
  editName.value = ws?.name ?? ''
}, { immediate: true })

function onSaveName() {
  const trimmed = editName.value.trim()
  if (!trimmed || !props.workspace) return
  emit('renamed', props.workspace.id, trimmed)
}

// ── Members ───────────────────────────────────────────────────────────────────

const members        = ref<WorkspaceMember[]>([])
const membersLoading = ref(false)

watch(
  [() => props.isOpen, () => props.workspace?.id],
  async ([open, id]) => {
    if (!open || !id) return
    membersLoading.value = true
    try {
      members.value = await source.listMembers(id)
    } catch {
      addToast({ type: 'error', title: 'No se pudieron cargar los miembros', duration: 3000 })
    } finally {
      membersLoading.value = false
    }
  },
)

type BadgeVariant = 'brand' | 'warning' | 'success' | 'neutral'

const ROLE_BADGE: Record<string, BadgeVariant> = {
  owner:  'brand',
  admin:  'warning',
  editor: 'success',
  viewer: 'neutral',
}

function roleBadgeVariant(name: string): BadgeVariant {
  return ROLE_BADGE[name.toLowerCase()] ?? 'neutral'
}

function initials(name: string): string {
  return name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase()
}

function canActOnMember(member: WorkspaceMember): boolean {
  if (member.role.name.toLowerCase() === 'owner') return false
  return can('workspace.members.change_role') || can('workspace.members.delete')
}

async function removeMember(memberId: string) {
  if (!props.workspace?.id) return
  try {
    await source.removeMember(props.workspace.id, memberId)
    members.value = members.value.filter(m => m.id !== memberId)
  } catch {
    addToast({ type: 'error', title: 'No se pudo eliminar el miembro', duration: 3000 })
  }
}

// ── Assign role ───────────────────────────────────────────────────────────────

const assignRoleOpen   = ref(false)
const assignRoleTarget = ref<WorkspaceMember | null>(null)

function openAssignRole(member: WorkspaceMember) {
  assignRoleTarget.value = member
  assignRoleOpen.value   = true
}

function onRoleAssigned(updated: WorkspaceMember) {
  const m = members.value.find(x => x.id === updated.id)
  if (m) m.role = updated.role
}

// ── Invite ────────────────────────────────────────────────────────────────────

const showInviteForm = ref(false)
const inviteEmails   = ref<string[]>([])
const inviteRole     = ref('')
const inviting       = ref(false)

const assignableRoleOptions = computed(() =>
  allRoles.value
    .filter(r => !(r.is_base && r.name.toLowerCase() === 'owner'))
    .map(r => ({ value: r.id, label: r.name })),
)

function openInviteForm() {
  showInviteForm.value = true
  ensureRolesLoaded()
  if (assignableRoleOptions.value.length) inviteRole.value = assignableRoleOptions.value[0].value
}

function cancelInvite() {
  showInviteForm.value = false
  inviteEmails.value   = []
  inviteRole.value     = assignableRoleOptions.value[0]?.value ?? ''
}

async function sendInvite() {
  if (inviteEmails.value.length === 0 || !inviteRole.value || !props.workspace?.id) return
  inviting.value = true
  const wsId = props.workspace.id
  try {
    await Promise.all(
      inviteEmails.value.map(email => source.inviteMember(wsId, email, inviteRole.value)),
    )
    const count = inviteEmails.value.length
    addToast({
      type:     'success',
      title:    count === 1 ? 'Invitación enviada' : `${count} invitaciones enviadas`,
      duration: 3000,
    })
    cancelInvite()
  } catch {
    addToast({ type: 'error', title: 'No se pudieron enviar las invitaciones', duration: 4000 })
  } finally {
    inviting.value = false
  }
}

// ── Roles ─────────────────────────────────────────────────────────────────────

const allRoles    = ref<WorkspaceRole[]>([])
const rolesLoading = ref(false)

const customRoles = computed(() => allRoles.value.filter(r => !r.is_base))
const baseRoles   = computed(() => allRoles.value.filter(r => r.is_base))

async function ensureRolesLoaded() {
  if (allRoles.value.length || rolesLoading.value || !props.workspace?.id) return
  rolesLoading.value = true
  try {
    allRoles.value = await source.listRoles(props.workspace.id)
  } catch {
    addToast({ type: 'error', title: 'No se pudieron cargar los roles', duration: 3000 })
  } finally {
    rolesLoading.value = false
  }
}

watch(activeSection, (s) => {
  if (s === 'roles' || s === 'members') ensureRolesLoaded()
})

const roleEditOpen = ref(false)
const editingRole  = ref<WorkspaceRole | null>(null)

function openCreateRole() { editingRole.value = null; roleEditOpen.value = true }
function openEditRole(role: WorkspaceRole) { editingRole.value = role; roleEditOpen.value = true }

function onRoleSaved(saved: WorkspaceRole) {
  const idx = allRoles.value.findIndex(r => r.id === saved.id)
  if (idx !== -1) allRoles.value[idx] = saved
  else allRoles.value.push(saved)
}

async function deleteRole(role: WorkspaceRole) {
  if (!props.workspace?.id) return
  try {
    await source.deleteRole(props.workspace.id, role.id)
    allRoles.value = allRoles.value.filter(r => r.id !== role.id)
    addToast({ type: 'success', title: 'Rol eliminado', duration: 3000 })
  } catch {
    addToast({ type: 'error', title: 'No se pudo eliminar', message: 'El rol puede tener miembros asignados.', duration: 5000 })
  }
}

// ── Danger ────────────────────────────────────────────────────────────────────

const archiveConfirm = ref(false)

function onArchive() {
  if (!props.workspace) return
  emit('archived', props.workspace.id)
}

// ── Close ─────────────────────────────────────────────────────────────────────

function onClose() {
  archiveConfirm.value = false
  emit('close')
}
</script>
