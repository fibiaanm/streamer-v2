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
            <AppInput v-model="editName" label="Nombre de la carpeta" />
            <div class="flex gap-2">
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

            <!-- Loading -->
            <template v-if="membersLoading">
              <div v-for="i in 3" :key="i" class="h-11 rounded-xl bg-white/4 animate-pulse" />
            </template>

            <!-- Member list -->
            <div v-else class="space-y-0.5">
              <MemberRow
                v-for="member in members"
                :key="member.id"
                :name="member.user.name"
                :sub="member.user.email"
                class="group"
              >
                <template #avatar>
                  <div
                    class="w-8 h-8 rounded-full bg-white/8 flex items-center justify-center text-[10px] font-bold text-white/50 uppercase shrink-0"
                  >
                    {{ initials(member.user.name) }}
                  </div>
                </template>
                <template #badge>
                  <AppBadge :variant="roleBadgeVariant(member.role.name)" size="sm">
                    {{ member.role.name }}
                  </AppBadge>
                </template>
                <template #actions>
                  <button
                    v-if="member.role.name.toLowerCase() !== 'owner'"
                    class="opacity-0 group-hover:opacity-100 transition-opacity w-6 h-6 flex items-center justify-center rounded-full hover:bg-white/8 text-white/30 hover:text-rose-400 cursor-pointer"
                    @click="removeMember(member.id)"
                  >
                    <AppIcon name="ui/x" size="sm" />
                  </button>
                  <div v-else class="w-6 shrink-0" />
                </template>
              </MemberRow>
            </div>

            <!-- Invite form -->
            <div class="border-t border-white/6 pt-4">
              <p class="text-[11px] font-semibold uppercase tracking-widest text-white/30 mb-3">Invitar</p>

              <div v-if="!showInviteForm">
                <AppButton variant="secondary" size="sm" icon="ui/plus" @click="openInviteForm">
                  Añadir miembro
                </AppButton>
              </div>

              <div v-else class="flex flex-col gap-3">
                <EmailTagInput v-model="inviteEmails" label="Correos electrónicos" />
                <AppSelect v-model="inviteRole" :options="roleSelectOptions" label="Rol" />
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
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import AppModal      from '@/components/AppModal.vue'
import AppInput      from '@/components/AppInput.vue'
import AppButton     from '@/components/AppButton.vue'
import AppIcon       from '@/components/AppIcon.vue'
import AppBadge      from '@/components/AppBadge.vue'
import AppSelect     from '@/components/AppSelect.vue'
import EmailTagInput from '@/components/EmailTagInput.vue'
import MemberRow     from '@/views/Settings/MemberRow.vue'
import { useWorkspacesApi } from '@/composables/api/useWorkspacesApi'
import { useToasts }        from '@/composables/core/useToasts'
import type { WorkspaceMember, WorkspaceRole } from '@/types'

const props = defineProps<{
  isOpen:     boolean
  workspace?: { id: string; name: string } | null
}>()

const emit = defineEmits<{
  close:    []
  renamed:  [id: string, name: string]
  archived: [id: string]
}>()

const api               = useWorkspacesApi()
const { add: addToast } = useToasts()

// ── Sections ──────────────────────────────────────────────────────────────────

const sections = [
  { id: 'general', label: 'General',  icon: 'ui/settings' },
  { id: 'members', label: 'Miembros', icon: 'ui/users'    },
  { id: 'danger',  label: 'Peligro',  icon: 'ui/shield'   },
]

const activeSection = ref('general')

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

// ── Reset on open ─────────────────────────────────────────────────────────────

watch(() => props.isOpen, (open) => {
  if (!open) return
  activeSection.value  = 'general'
  archiveConfirm.value = false
  showInviteForm.value = false
  inviteEmails.value   = []
  inviteRole.value     = ''
})

// ── Members ───────────────────────────────────────────────────────────────────

const members        = ref<WorkspaceMember[]>([])
const membersLoading = ref(false)

watch(
  [() => props.isOpen, () => props.workspace?.id],
  async ([open, id]) => {
    if (!open || !id) return
    membersLoading.value = true
    try {
      members.value = (await api.listMembers(id)).data.data
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

async function removeMember(memberId: string) {
  if (!props.workspace?.id) return
  try {
    await api.removeMember(props.workspace.id, memberId)
    members.value = members.value.filter(m => m.id !== memberId)
  } catch {
    addToast({ type: 'error', title: 'No se pudo eliminar el miembro', duration: 3000 })
  }
}

// ── Invite ────────────────────────────────────────────────────────────────────

const showInviteForm = ref(false)
const inviteEmails   = ref<string[]>([])
const inviteRole     = ref('')
const inviting       = ref(false)
const allRoles       = ref<WorkspaceRole[]>([])

const roleSelectOptions = computed(() =>
  allRoles.value
    .filter(r => !(r.is_base && r.name.toLowerCase() === 'owner'))
    .map(r => ({ value: r.id, label: r.name })),
)

async function openInviteForm() {
  showInviteForm.value = true
  if (allRoles.value.length || !props.workspace?.id) return
  try {
    allRoles.value = (await api.listRoles(props.workspace.id)).data.data
    if (roleSelectOptions.value.length) inviteRole.value = roleSelectOptions.value[0].value
  } catch {
    addToast({ type: 'error', title: 'No se pudieron cargar los roles', duration: 3000 })
  }
}

function cancelInvite() {
  showInviteForm.value = false
  inviteEmails.value   = []
  inviteRole.value     = roleSelectOptions.value[0]?.value ?? ''
}

async function sendInvite() {
  if (inviteEmails.value.length === 0 || !inviteRole.value || !props.workspace?.id) return
  inviting.value = true
  const wsId = props.workspace.id
  try {
    await Promise.all(
      inviteEmails.value.map(email => api.inviteMember(wsId, email, inviteRole.value)),
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
