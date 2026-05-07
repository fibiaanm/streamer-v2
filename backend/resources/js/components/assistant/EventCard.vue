<template>
  <div class="group relative glass rounded-2xl px-4 py-3 flex flex-col gap-2">

    <!-- Header row -->
    <div class="flex items-start gap-2">
      <div class="flex-1 min-w-0">
        <div v-if="!editing" class="flex items-center gap-2 flex-wrap">
          <AppIcon v-if="event.series_id" name="ui/repeat" size="xs" class="text-white/35 shrink-0" />
          <p class="text-sm text-white/90 break-words">{{ event.content }}</p>
        </div>

        <!-- Edit form -->
        <div v-else class="flex flex-col gap-2">
          <textarea
            v-model="editForm.content"
            rows="2"
            class="w-full bg-white/8 border border-white/15 rounded-xl px-3 py-2 text-sm text-white/90
                   resize-none outline-none focus:border-brand-400/40 transition-colors"
          />
          <div class="flex gap-2">
            <input
              v-model="editForm.event_at"
              type="datetime-local"
              class="flex-1 bg-white/8 border border-white/15 rounded-xl px-3 py-1.5 text-xs text-white/80
                     outline-none focus:border-brand-400/40 transition-colors"
            />
            <input
              v-model="editForm.type"
              type="text"
              placeholder="Tipo"
              class="w-28 bg-white/8 border border-white/15 rounded-xl px-3 py-1.5 text-xs text-white/80
                     outline-none focus:border-brand-400/40 transition-colors"
            />
          </div>
          <div class="flex gap-2">
            <button
              @click="saveEdit"
              :disabled="saving"
              class="px-3 py-1 rounded-lg text-xs font-medium glass-brand text-brand-300
                     hover:text-brand-200 transition-colors disabled:opacity-50"
            >
              {{ saving ? 'Guardando…' : 'Guardar' }}
            </button>
            <button
              @click="cancelEdit"
              class="px-3 py-1 rounded-lg text-xs text-white/40 hover:text-white/70 transition-colors"
            >
              Cancelar
            </button>
          </div>
        </div>
      </div>

      <!-- Hover actions -->
      <div
        v-if="!editing && !confirmCancel && !snoozing"
        class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity shrink-0"
      >
        <button
          @click="startEdit"
          class="w-7 h-7 flex items-center justify-center rounded-lg text-white/35
                 hover:text-white/80 hover:bg-white/8 transition-colors cursor-pointer"
          title="Editar"
        >
          <AppIcon name="ui/pencil" size="xs" />
        </button>
        <button
          @click="snoozing = true"
          class="w-7 h-7 flex items-center justify-center rounded-lg text-white/35
                 hover:text-white/80 hover:bg-white/8 transition-colors cursor-pointer"
          title="Posponer"
        >
          <AppIcon name="ui/clock" size="xs" />
        </button>
        <button
          @click="confirmCancel = true"
          class="w-7 h-7 flex items-center justify-center rounded-lg text-white/35
                 hover:text-red-400/70 hover:bg-white/8 transition-colors cursor-pointer"
          title="Cancelar"
        >
          <AppIcon name="ui/x" size="xs" />
        </button>
      </div>
    </div>

    <!-- Meta row -->
    <div v-if="!editing" class="flex items-center gap-2 flex-wrap">
      <span class="text-xs text-white/45">{{ formattedDate }}</span>
      <span v-if="event.event_end" class="text-xs text-white/25">→ {{ formattedEnd }}</span>
      <span
        v-if="event.type"
        class="px-2 py-0.5 rounded-md text-[10px] font-medium bg-white/8 text-white/45"
      >
        {{ event.type }}
      </span>
      <span v-if="event.virtual" class="px-2 py-0.5 rounded-md text-[10px] bg-white/5 text-white/25">
        ocurrencia virtual
      </span>
    </div>

    <!-- Reminders -->
    <div v-if="!editing && event.reminders.length" class="flex flex-col gap-1">
      <div
        v-for="r in event.reminders"
        :key="r.id"
        class="flex items-center gap-2 text-xs text-white/35"
      >
        <AppIcon
          :name="r.status === 'fired' ? 'ui/check' : 'ui/clock'"
          size="xs"
          :class="r.status === 'fired' ? 'text-white/25' : 'text-brand-400/50'"
        />
        <span>{{ r.message }}</span>
        <span class="text-white/20">· {{ formatDatetime(r.fire_at) }}</span>
      </div>
    </div>

    <!-- Snooze picker -->
    <div v-if="snoozing" class="flex items-center gap-2">
      <input
        v-model="snoozeUntil"
        type="datetime-local"
        class="flex-1 bg-white/8 border border-white/15 rounded-xl px-3 py-1.5 text-xs text-white/80
               outline-none focus:border-brand-400/40 transition-colors"
      />
      <button
        @click="doSnooze"
        :disabled="!snoozeUntil || saving"
        class="px-3 py-1.5 rounded-lg text-xs font-medium glass-brand text-brand-300
               hover:text-brand-200 transition-colors disabled:opacity-50"
      >
        Posponer
      </button>
      <button
        @click="snoozing = false"
        class="px-3 py-1.5 rounded-lg text-xs text-white/40 hover:text-white/70 transition-colors"
      >
        Cancelar
      </button>
    </div>

    <!-- Cancel confirm -->
    <div v-if="confirmCancel" class="flex items-center gap-2 flex-wrap">
      <span class="text-xs text-white/50">¿Cancelar?</span>
      <button
        v-if="event.series_id"
        @click="doCancel(false)"
        :disabled="saving"
        class="px-3 py-1 rounded-lg text-xs text-white/60 hover:text-white/90 hover:bg-white/8 transition-colors"
      >
        Solo este
      </button>
      <button
        v-if="event.series_id"
        @click="doCancel(true)"
        :disabled="saving"
        class="px-3 py-1 rounded-lg text-xs text-red-400/70 hover:text-red-300 hover:bg-white/8 transition-colors"
      >
        Toda la serie
      </button>
      <button
        v-if="!event.series_id"
        @click="doCancel(false)"
        :disabled="saving"
        class="px-3 py-1 rounded-lg text-xs text-red-400/70 hover:text-red-300 hover:bg-white/8 transition-colors"
      >
        Confirmar
      </button>
      <button
        @click="confirmCancel = false"
        class="px-3 py-1 rounded-lg text-xs text-white/35 hover:text-white/70 transition-colors"
      >
        No
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import AppIcon from '@/components/AppIcon.vue'
import type { AssistantEvent } from '@/types'
import { useDate } from '@/composables/core/useDate'

const props = defineProps<{ event: AssistantEvent }>()
const emit  = defineEmits<{
  cancel: [id: string, series: boolean]
  snooze: [id: string, until: string]
  update: [id: string, payload: Partial<AssistantEvent>]
}>()

const { formatEventAt, formatTime, formatDatetime } = useDate()

const editing       = ref(false)
const confirmCancel = ref(false)
const snoozing      = ref(false)
const saving        = ref(false)
const snoozeUntil   = ref('')

const editForm = ref({
  content:  props.event.content,
  event_at: props.event.event_at ? toLocalDatetimeInput(props.event.event_at) : '',
  type:     props.event.type ?? '',
})

function toLocalDatetimeInput(iso: string): string {
  const d   = new Date(iso)
  const pad = (n: number) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`
}

const formattedDate = computed(() => formatEventAt(props.event.event_at))

const formattedEnd = computed(() => props.event.event_end ? formatTime(props.event.event_end) : '')

function startEdit() {
  editForm.value = {
    content:  props.event.content,
    event_at: props.event.event_at ? toLocalDatetimeInput(props.event.event_at) : '',
    type:     props.event.type ?? '',
  }
  editing.value = true
}

function cancelEdit() {
  editing.value = false
}

async function saveEdit() {
  saving.value = true
  try {
    emit('update', props.event.id, {
      content:  editForm.value.content || undefined,
      event_at: editForm.value.event_at ? new Date(editForm.value.event_at).toISOString() : undefined,
      type:     editForm.value.type || undefined,
    })
    editing.value = false
  } finally {
    saving.value = false
  }
}

async function doCancel(series: boolean) {
  saving.value = true
  try {
    emit('cancel', props.event.id, series)
  } finally {
    saving.value      = false
    confirmCancel.value = false
  }
}

async function doSnooze() {
  if (!snoozeUntil.value) return
  saving.value = true
  try {
    emit('snooze', props.event.id, new Date(snoozeUntil.value).toISOString())
    snoozing.value = false
  } finally {
    saving.value = false
  }
}
</script>
