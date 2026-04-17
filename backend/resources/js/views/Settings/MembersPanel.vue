<template>
  <div class="h-full flex flex-col p-8 gap-6 overflow-y-auto">

    <!-- Header -->
    <div class="flex items-center justify-between">
      <p class="text-xs text-white/30">{{ members.length }} miembro{{ members.length !== 1 ? 's' : '' }}</p>
      <AppButton v-if="canInviteMembers" size="sm" icon="ui/plus">
        Invitar
      </AppButton>
    </div>

    <!-- Member list -->
    <div class="space-y-1">
      <div
        v-for="m in sortedMembers"
        :key="m.id"
        class="group flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-white/4 transition-colors"
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

        <!-- Remove -->
        <button
          v-if="canRemoveMembers && m.role.name !== 'owner'"
          class="opacity-0 group-hover:opacity-100 transition-opacity p-1 rounded-lg hover:bg-white/8 text-white/30 hover:text-rose-400 cursor-pointer"
          @click="confirmRemove(m)"
        >
          <AppIcon name="ui/x" size="sm" />
        </button>
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
            class="opacity-0 group-hover:opacity-100 transition-opacity p-1 rounded-lg hover:bg-white/8 text-white/30 hover:text-rose-400 cursor-pointer"
            @click="cancelInvite(inv)"
          >
            <AppIcon name="ui/x" size="sm" />
          </button>
          <div v-else class="w-6 shrink-0" />
        </div>
      </div>
    </template>

  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { usePermissions } from '@/composables/core/usePermissions'
import type { Member, Invitation } from '@/composables/api/useMembersApi'
import AppButton from '@/components/AppButton.vue'
import AppBadge  from '@/components/AppBadge.vue'
import AppIcon   from '@/components/AppIcon.vue'

const { canInviteMembers, canRemoveMembers } = usePermissions()

// ── Dummy data ───────────────────────────────────────────────────────────────
const members = ref<Member[]>([
  { id: '1', status: 'active', user: { id: '1', name: 'Ana García',    email: 'ana@acme.com'    }, role: { id: '1', name: 'owner'  } },
  { id: '2', status: 'active', user: { id: '2', name: 'Carlos López',  email: 'carlos@acme.com' }, role: { id: '2', name: 'admin'  } },
  { id: '3', status: 'active', user: { id: '3', name: 'María Torres',  email: 'maria@acme.com'  }, role: { id: '3', name: 'member' } },
  { id: '4', status: 'active', user: { id: '4', name: 'Pedro Ruiz',    email: 'pedro@acme.com'  }, role: { id: '4', name: 'member' } },
])

const pendingInvitations = ref<Invitation[]>([
  {
    id: '1',
    email: 'lucia@acme.com',
    status: 'pending',
    expires_at: '2026-04-24T00:00:00Z',
    role: { id: '3', name: 'member' },
    invited_by: { id: '1', name: 'Ana García' },
  },
])

// ── Sorting ──────────────────────────────────────────────────────────────────
const ROLE_ORDER: Record<string, number> = { owner: 0, admin: 1, billing: 2, member: 3 }

const sortedMembers = computed(() =>
  [...members.value].sort((a, b) => {
    const wa = ROLE_ORDER[a.role.name] ?? 99
    const wb = ROLE_ORDER[b.role.name] ?? 99
    return wa - wb
  }),
)

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

// ── Actions (stubs — conectar con API cuando backend esté listo) ─────────────
const confirmRemove  = (_m: Member)     => {}
const cancelInvite   = (_inv: Invitation) => {}

// ── Utils ────────────────────────────────────────────────────────────────────
const formatExpiry = (iso: string) => {
  const d = new Date(iso)
  return d.toLocaleDateString('es-ES', { day: 'numeric', month: 'short' })
}
</script>
