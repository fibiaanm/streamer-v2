<template>
  <div class="flex flex-col p-8 gap-6">

    <!-- Header -->
    <div class="flex items-center justify-between">
      <p class="text-xs text-white/30">
        {{ members.length }}<template v-if="membersMax !== null"> / {{ membersMax }}</template> miembro{{ members.length !== 1 ? 's' : '' }}
      </p>
      <AppButton v-if="canInviteMembers" size="sm" icon="ui/plus" @click="inviteModalOpen = true">
        Invitar
      </AppButton>
    </div>

    <!-- Member list -->
    <div class="space-y-1">
      <MemberRow
        v-for="m in sortedMembers"
        :key="m.id"
        :name="m.user.name"
        :sub="m.user.email"
        class="group"
        :class="(hoveredMemberId === m.id || openMenuId === m.id) ? 'bg-white/4' : ''"
        @mouseenter="hoveredMemberId = m.id"
        @mouseleave="hoveredMemberId = null"
      >
        <template #avatar>
          <UserAvatar :user="m.user" />
        </template>
        <template #badge>
          <AppBadge :variant="roleBadgeVariant(m.role.name)" size="sm">
            {{ m.role.name }}
          </AppBadge>
        </template>
        <template #actions>
          <AppDropdown
            v-if="canActOn(m)"
            align="right"
            @update:open="val => openMenuId = val ? m.id : null"
          >
            <template #trigger>
              <button
                class="transition-opacity w-6 h-6 flex items-center justify-center rounded-full hover:bg-white/8 text-white/40 hover:text-white/70 cursor-pointer"
                :class="openMenuId === m.id ? 'opacity-100' : 'opacity-0 group-hover:opacity-100'"
              >
                <AppIcon name="ui/more-horizontal" size="sm" />
              </button>
            </template>
            <AppDropdownItem v-if="canAssignRoles" icon="ui/shield" @click="openAssignRole(m)">
              Cambiar rol
            </AppDropdownItem>
            <AppDropdownItem v-if="canRemoveMembers" icon="ui/user-x" variant="danger" @click="confirmRemove(m)">
              Eliminar
            </AppDropdownItem>
          </AppDropdown>
          <div v-else class="w-6 shrink-0" />
        </template>
      </MemberRow>
    </div>

    <!-- Pending invitations -->
    <template v-if="pendingInvitations.length">
      <div class="border-t border-white/8 pt-4 space-y-1">
        <p class="text-xs text-white/30 mb-3">Invitaciones pendientes</p>
        <MemberRow
          v-for="inv in pendingInvitations"
          :key="inv.id"
          class="group hover:bg-white/4"
        >
          <template #avatar>
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-semibold shrink-0 bg-white/6 text-white/30">
              ?
            </div>
          </template>
          <template #text>
            <p class="text-sm text-white/50 truncate">{{ inv.email }}</p>
            <p class="text-xs text-white/25 truncate">Expira {{ formatExpiry(inv.expires_at) }}</p>
          </template>
          <template #badge>
            <AppBadge :variant="roleBadgeVariant(inv.role.name)" size="sm">
              {{ inv.role.name }}
            </AppBadge>
          </template>
          <template #actions>
            <button
              v-if="canInviteMembers"
              class="opacity-0 group-hover:opacity-100 transition-opacity w-6 h-6 flex items-center justify-center rounded-full hover:bg-white/8 text-white/30 hover:text-rose-400 cursor-pointer"
              @click="cancelInvite(inv)"
            >
              <AppIcon name="ui/x" size="sm" />
            </button>
            <div v-else class="w-6 shrink-0" />
          </template>
        </MemberRow>
      </div>
    </template>

    <!-- Suspended members -->
    <template v-if="canRemoveMembers">
      <div class="border-t border-white/8 pt-4">
        <button
          v-if="!suspendedLoaded"
          class="text-xs text-white/30 hover:text-white/50 transition-colors cursor-pointer"
          :disabled="suspendedLoading"
          @click="loadSuspended"
        >
          {{ suspendedLoading ? 'Cargando…' : 'Mostrar miembros inactivos' }}
        </button>

        <template v-else>
          <p class="text-xs text-white/30 mb-3">Inactivos</p>
          <div v-if="suspendedMembers.length" class="space-y-1">
            <MemberRow
              v-for="m in suspendedMembers"
              :key="m.id"
              :name="m.user.name"
              :sub="m.user.email"
              class="opacity-50"
            >
              <template #avatar>
                <UserAvatar :user="m.user" />
              </template>
              <template #badge>
                <AppBadge variant="neutral" size="sm">inactivo</AppBadge>
              </template>
            </MemberRow>
          </div>
          <p v-else class="text-xs text-white/25 px-3">No hay miembros inactivos.</p>
        </template>
      </div>
    </template>

  </div>

  <InviteMembersModal
    :is-open="inviteModalOpen"
    @close="inviteModalOpen = false"
    @invited="onInvited"
  />

  <AssignRoleModal
    :is-open="assignRoleModalOpen"
    :member="assignRoleTarget"
    @close="assignRoleModalOpen = false"
    @saved="onRoleAssigned"
  />
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { usePermissions }  from '@/composables/core/usePermissions'
import { useSession }      from '@/composables/core/useSession'
import { useMembersApi }   from '@/composables/api/useMembersApi'
import { useMembersState } from '@/composables/core/useMembersState'
import { useMembersSync }  from '@/composables/core/useMembersSync'
import { useToasts }       from '@/composables/core/useToasts'
import type { Member } from '@/composables/api/useMembersApi'
import AppButton          from '@/components/AppButton.vue'
import AppBadge           from '@/components/AppBadge.vue'
import AppIcon            from '@/components/AppIcon.vue'
import AppDropdown        from '@/components/AppDropdown.vue'
import AppDropdownItem    from '@/components/AppDropdownItem.vue'
import UserAvatar         from '@/components/UserAvatar.vue'
import MemberRow          from '@/views/Settings/MemberRow.vue'
import InviteMembersModal from '@/views/Settings/InviteMembersModal.vue'
import AssignRoleModal    from '@/views/Settings/AssignRoleModal.vue'

const { canInviteMembers, canRemoveMembers, canAssignRoles, membersMax } = usePermissions()
const { removeMember, cancelInvitation, listSuspendedMembers } = useMembersApi()
const { user }   = useSession()
const { add: addToast, remove: removeToast } = useToasts()

const membersState = useMembersState()
const { members, invitations: pendingInvitations } = membersState
useMembersSync(membersState)

const inviteModalOpen     = ref(false)
const openMenuId          = ref<string | null>(null)
const hoveredMemberId     = ref<string | null>(null)
const assignRoleModalOpen = ref(false)
const assignRoleTarget    = ref<Member | null>(null)
const suspendedMembers    = ref<Member[]>([])
const suspendedLoaded     = ref(false)
const suspendedLoading    = ref(false)

onMounted(membersState.loadData)

const onInvited = () => membersState.loadData()

const loadSuspended = async () => {
  suspendedLoading.value = true
  try {
    const res = await listSuspendedMembers()
    suspendedMembers.value = res.data.data
    suspendedLoaded.value  = true
  } finally {
    suspendedLoading.value = false
  }
}

// ── Sorting ──────────────────────────────────────────────────────────────────
const ROLE_ORDER: Record<string, number> = { owner: 0, admin: 1, billing: 2, member: 3 }

const sortedMembers = computed(() =>
  [...members.value].sort((a, b) => {
    const wa = ROLE_ORDER[a.role.name] ?? 99
    const wb = ROLE_ORDER[b.role.name] ?? 99
    return wa - wb
  }),
)

// ── Guards ───────────────────────────────────────────────────────────────────
const isRemovable = (m: Member) =>
  m.user.id !== user.value?.id && m.role.name !== 'owner'

const canActOn = (m: Member) =>
  isRemovable(m) && (canAssignRoles.value || canRemoveMembers.value)

const openAssignRole = (m: Member) => {
  assignRoleTarget.value    = m
  assignRoleModalOpen.value = true
}

const onRoleAssigned = (updated: Member) => {
  const m = membersState.members.value.find(x => x.id === updated.id)
  if (m) m.role = updated.role
}

// ── Remove with undo toast ───────────────────────────────────────────────────
const confirmRemove = (m: Member) => {
  const toastId = addToast({
    type:     'warning',
    title:    `${m.user.name} será eliminado`,
    duration: 5000,
    actions:  [{
      label:   'Cancelar',
      onClick: () => removeToast(toastId),
    }],
    onTimeout: async () => {
      await removeMember(m.id)
      membersState.members.value = membersState.members.value.filter(x => x.id !== m.id)
    },
  })
}

// ── Role badge ───────────────────────────────────────────────────────────────
type BadgeVariant = 'brand' | 'warning' | 'success' | 'neutral'
const ROLE_BADGE: Record<string, BadgeVariant> = {
  owner:   'brand',
  admin:   'warning',
  billing: 'success',
  member:  'neutral',
}
const roleBadgeVariant = (role: string): BadgeVariant => ROLE_BADGE[role] ?? 'neutral'

// ── Cancel invitation ─────────────────────────────────────────────────────────
const cancelInvite = async (inv: { id: string }) => {
  await cancelInvitation(inv.id)
  pendingInvitations.value = pendingInvitations.value.filter(i => i.id !== inv.id)
}

// ── Utils ────────────────────────────────────────────────────────────────────
const formatExpiry = (iso: string) => {
  const d = new Date(iso)
  return d.toLocaleDateString('es-ES', { day: 'numeric', month: 'short' })
}
</script>
