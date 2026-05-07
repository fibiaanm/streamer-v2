<template>
  <div class="flex flex-col h-full">

    <!-- Header -->
    <div class="px-4 pt-4 pb-3 border-b border-white/8 shrink-0">
      <div class="flex items-center gap-2">
        <p class="text-sm font-semibold text-white/90 truncate flex-1">{{ list.name }}</p>
        <span
          v-if="list.type"
          class="text-[10px] font-medium uppercase tracking-wide text-white/35
                 bg-white/8 rounded-md px-1.5 py-0.5 shrink-0"
        >
          {{ list.type }}
        </span>
      </div>
      <p class="text-xs text-white/30 mt-0.5">
        {{ list.items_count.pending }} pendiente{{ list.items_count.pending !== 1 ? 's' : '' }}
        <span v-if="list.items_count.done > 0"> · {{ list.items_count.done }} completado{{ list.items_count.done !== 1 ? 's' : '' }}</span>
      </p>
    </div>

    <!-- Add item -->
    <div v-if="canWrite" class="px-3 py-2 border-b border-white/6 shrink-0">
      <div class="flex items-center gap-2">
        <input
          v-model="newItemContent"
          type="text"
          placeholder="Añadir ítem…"
          class="flex-1 bg-transparent text-sm text-white/80 placeholder-white/25
                 outline-none py-1"
          @keydown.enter.prevent="handleAddItem"
        />
        <button
          v-if="newItemContent.trim()"
          @click="handleAddItem"
          :disabled="adding"
          class="text-brand-300 hover:text-brand-200 transition-colors disabled:opacity-40"
        >
          <AppIcon name="ui/plus" size="xs" />
        </button>
      </div>
    </div>

    <!-- Loading state -->
    <div v-if="loadingDetail" class="flex-1 flex items-center justify-center">
      <div class="flex flex-col gap-2 w-full px-3 py-3">
        <div v-for="i in 4" :key="i" class="h-7 rounded-xl bg-white/5 animate-pulse" />
      </div>
    </div>

    <!-- Items -->
    <div v-else class="flex-1 overflow-y-auto pretty-scroll px-3 py-2 flex flex-col gap-0.5">

      <!-- Pending -->
      <template v-if="pendingItems.length > 0">
        <div
          v-for="item in pendingItems"
          :key="item.id"
          class="group/item flex items-center gap-2.5 px-2 py-1.5 rounded-xl hover:bg-white/5 transition-colors"
        >
          <button
            class="w-4 h-4 rounded-md border border-white/20 flex items-center justify-center
                   hover:border-brand-400/50 transition-colors shrink-0"
            :disabled="toggling.has(item.id)"
            @click="handleToggle(item, true)"
          >
            <AppIcon v-if="toggling.has(item.id)" name="ui/loader" size="xs" class="text-white/30 animate-spin" />
          </button>
          <span class="flex-1 text-sm text-white/80 break-words min-w-0">{{ item.content }}</span>
          <button
            v-if="canRemoveItem(item)"
            class="opacity-0 group-hover/item:opacity-100 text-white/25 hover:text-red-400 transition-all"
            @click="handleRemove(item)"
          >
            <AppIcon name="ui/x" size="xs" />
          </button>
        </div>
      </template>

      <p v-else-if="doneItems.length === 0" class="text-xs text-white/25 px-2 py-3">
        Sin ítems. Añade el primero arriba.
      </p>

      <!-- Done — collapsible -->
      <template v-if="doneItems.length > 0">
        <button
          class="flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-widest
                 text-white/20 hover:text-white/40 transition-colors mt-2 px-2 cursor-pointer"
          @click="doneExpanded = !doneExpanded"
        >
          <AppIcon
            name="ui/chevron-down"
            size="xs"
            :class="doneExpanded ? 'rotate-0' : '-rotate-90'"
            class="transition-transform duration-150"
          />
          Completados ({{ doneItems.length }})
        </button>

        <template v-if="doneExpanded">
          <div
            v-for="item in doneItems"
            :key="item.id"
            class="group/item flex items-center gap-2.5 px-2 py-1.5 rounded-xl hover:bg-white/4 transition-colors"
          >
            <button
              class="w-4 h-4 rounded-md border border-brand-400/30 bg-brand-400/10 flex items-center
                     justify-center hover:border-white/20 hover:bg-transparent transition-colors shrink-0"
              :disabled="toggling.has(item.id)"
              @click="handleToggle(item, false)"
            >
              <AppIcon v-if="!toggling.has(item.id)" name="ui/check" size="xs" class="text-brand-400/60" />
              <AppIcon v-else name="ui/loader" size="xs" class="text-white/30 animate-spin" />
            </button>
            <span class="flex-1 text-sm text-white/35 line-through break-words min-w-0">{{ item.content }}</span>
            <button
              v-if="canRemoveItem(item)"
              class="opacity-0 group-hover/item:opacity-100 text-white/20 hover:text-red-400 transition-all"
              @click="handleRemove(item)"
            >
              <AppIcon name="ui/x" size="xs" />
            </button>
          </div>
        </template>
      </template>
    </div>

    <!-- Footer: clear completed -->
    <div
      v-if="!loadingDetail && doneItems.length > 0 && isOwner"
      class="px-3 py-3 border-t border-white/6 shrink-0"
    >
      <button
        @click="handleClearCompleted"
        :disabled="clearing"
        class="w-full text-xs text-white/30 hover:text-white/60 transition-colors
               text-left disabled:opacity-40"
      >
        {{ clearing ? 'Limpiando…' : `Limpiar ${doneItems.length} completado${doneItems.length !== 1 ? 's' : ''}` }}
      </button>
    </div>

  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import AppIcon from '@/components/AppIcon.vue'
import { useLists } from '@/composables/assistant/useLists'
import { useSession } from '@/composables/core/useSession'
import type { AssistantList, ListItem } from '@/types'

const props = defineProps<{ list: AssistantList }>()

const { loadingDetail, addItem, toggleItem, removeItem, clearCompleted } = useLists()
const { user } = useSession()

const newItemContent = ref('')
const adding         = ref(false)
const clearing       = ref(false)
const doneExpanded   = ref(false)
const toggling       = ref(new Set<string>())

const isOwner  = computed(() => !props.list.is_shared_with_me)
const canWrite = computed(() => props.list.my_permission === 'write')

const pendingItems = computed(() => props.list.items?.filter((i) => i.status === 'pending') ?? [])
const doneItems    = computed(() => props.list.items?.filter((i) => i.status === 'done') ?? [])

function canRemoveItem(item: ListItem): boolean {
    if (isOwner.value) return true
    return canWrite.value && item.added_by === user.value?.id
}

async function handleAddItem() {
    const content = newItemContent.value.trim()
    if (!content || adding.value) return
    adding.value = true
    try {
        await addItem(props.list.id, content)
        newItemContent.value = ''
    } finally {
        adding.value = false
    }
}

async function handleToggle(item: ListItem, done: boolean) {
    toggling.value.add(item.id)
    try {
        await toggleItem(props.list.id, item.id, done)
    } finally {
        toggling.value.delete(item.id)
    }
}

async function handleRemove(item: ListItem) {
    await removeItem(props.list.id, item.id)
}

async function handleClearCompleted() {
    clearing.value = true
    try {
        await clearCompleted(props.list.id)
        doneExpanded.value = false
    } finally {
        clearing.value = false
    }
}
</script>
