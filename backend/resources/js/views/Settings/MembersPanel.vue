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
      <div
        v-for="m in sortedMembers"
        :key="m.id"
        class="group flex items-center gap-3 px-3 py-2.5 rounded-xl transition-colors"
        :class="(hoveredMemberId === m.id || openMenuId === m.id) ? 'bg-white/4' : ''"
        @mouseenter="hoveredMemberId = m.id"
        @mouseleave="hoveredMemberId = null"
      >
        <!-- Avatar -->
        <div :class="['w-8 h-8 rounded-full flex items-center justify-center text-xs font-semibold shrink-0', avatarColor(m.user.name)]">
          {{ initials(m.user.name) }}
        </div>

        <!-- Name + email -->
        <div class="flex-1 min-w-0">
          <p class="text-sm text-white/80 truncate">{{ m.user.name }}</p>
          <p class="text-xs text-white/30 truncate">{{ m.user.email }}</p>
        </div>

        <!-- Role badge -->
        <AppBadge :variant="roleBadgeVariant(m.role.name)" size="sm">
          {{ m.role.name }}
        </AppBadge>

        <!-- Actions: three-dots (only for removable members) -->
        <AppDropdown
          v-if="canRemoveMembers && isRemovable(m)"
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
          <AppDropdownItem icon="ui/user-x" variant="danger" @click="confirmRemove(m)">
            Eliminar
          </AppDropdownItem>
        </AppDropdown>
        <!-- Spacer when no dropdown so badge doesn't shift -->
        <div v-else class="w-6 shrink-0" />

      </div>
    </div>

    <!-- Pending invitations -->
    <template v-if="pendingInvitations.length">
      <div class="border-t border-white/8 pt-4 space-y-1">
        <p class="text-xs text-white/30 mb-3">Invitaciones pendientes</p>
        <div
          v-for="inv in pendingInvitations"
          :key="inv.id"
          class="group flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-white/4 transition-colors"
        >
          <!-- Placeholder avatar -->
          <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-semibold shrink-0 bg-white/6 text-white/30">
            ?
          </div>

          <!-- Email -->
          <div class="flex-1 min-w-0">
            <p class="text-sm text-white/50 truncate">{{ inv.email }}</p>
            <p class="text-xs text-white/25 truncate">Expira {{ formatExpiry(inv.expires_at) }}</p>
          </div>

          <!-- Role badge -->
          <AppBadge :variant="roleBadgeVariant(inv.role.name)" size="sm">
            {{ inv.role.name }}
          </AppBadge>

          <!-- Cancel -->
          <button
            v-if="canInviteMembers"
            class="opacity-0 group-hover:opacity-100 transition-opacity w-6 h-6 flex items-center justify-center rounded-full hover:bg-white/8 text-white/30 hover:text-rose-400 cursor-pointer"
            @click="cancelInvite(inv)"
          >
            <AppIcon name="ui/x" size="sm" />
          </button>
          <div v-else class="w-6 shrink-0" />
        </div>
      </div>
    </template>

  </div>

  <InviteMembersModal
    :is-open="inviteModalOpen"
    @close="inviteModalOpen = false"
    @invited="onInvited"
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
import InviteMembersModal from '@/views/Settings/InviteMembersModal.vue'

const { canInviteMembers, canRemoveMembers, membersMax } = usePermissions()
const { removeMember, cancelInvitation } = useMembersApi()
const { user }   = useSession()
const { add: addToast, remove: removeToast } = useToasts()

const membersState = useMembersState()
const { members, invitations: pendingInvitations } = membersState
useMembersSync(membersState)

const inviteModalOpen = ref(false)
const openMenuId      = ref<string | null>(null)
const hoveredMemberId = ref<string | null>(null)

onMounted(membersState.loadData)

const onInvited = () => membersState.loadData()

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

// ── Avatar ───────────────────────────────────────────────────────────────────
const AVATAR_COLORS = [
  'bg-sky-500/20 text-sky-300',
  'bg-purple-500/20 text-purple-300',
  'bg-emerald-500/20 text-emerald-300',
  'bg-amber-500/20 text-amber-300',
  'bg-rose-500/20 text-rose-300',
]

const initials = (name: string) =>
  name.split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase()

const avatarColor = (name: string) => {
  const hash = [...name].reduce((acc, c) => acc + c.charCodeAt(0), 0)
  return AVATAR_COLORS[hash % AVATAR_COLORS.length]
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
