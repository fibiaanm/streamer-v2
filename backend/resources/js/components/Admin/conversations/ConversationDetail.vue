<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-150"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="convId"
        class="fixed inset-0 z-50 flex items-start justify-end bg-black/60 backdrop-blur-sm"
        @click.self="close"
      >
        <div class="h-full w-full max-w-3xl bg-[#0e0e12] border-l border-white/8 flex flex-col overflow-hidden">

          <!-- Header -->
          <div class="flex items-start justify-between px-6 py-5 border-b border-white/8 shrink-0">
            <div v-if="detail">
              <p class="font-medium text-white/90">{{ detail.title ?? `Session #${detail.id}` }}</p>
              <p class="text-xs text-white/40 mt-0.5">{{ detail.user_name }} · {{ detail.user_email }}</p>
              <p class="text-xs text-white/25 mt-0.5">
                ID {{ detail.id }} · {{ formatDate(detail.created_at) }}
                · {{ detail.message_count }} msgs · {{ fmt(detail.cost?.total ?? detail.total_tokens ?? 0) }} tokens
              </p>
            </div>
            <div v-else class="text-sm text-white/40">Loading…</div>
            <button
              class="ml-4 text-white/40 hover:text-white/80 transition-colors shrink-0 cursor-pointer"
              @click="close"
            >✕</button>
          </div>

          <!-- Body -->
          <div class="flex-1 overflow-y-auto px-5 py-4 space-y-2">
            <div v-if="loadingDetail" class="py-10 text-center text-sm text-white/25">Loading conversation…</div>

            <template v-else-if="detail">
              <div v-for="m in detail.messages" :key="m.id">

                <!-- user -->
                <div v-if="m.role === 'user'" class="flex justify-end">
                  <div class="max-w-[75%]">
                    <div class="bg-brand-500/15 border border-brand-500/20 rounded-2xl rounded-br-sm px-4 py-2.5 text-sm text-white/85 whitespace-pre-wrap">{{ m.content }}</div>
                    <p class="text-right text-[10px] text-white/25 mt-1 px-1">{{ m.channel }} · {{ formatTime(m.created_at) }}</p>
                  </div>
                </div>

                <!-- assistant -->
                <div v-else-if="m.role === 'assistant'" class="flex justify-start">
                  <div class="max-w-[75%]">
                    <div class="bg-white/5 border border-white/8 rounded-2xl rounded-bl-sm px-4 py-2.5 text-sm text-white/75 whitespace-pre-wrap">{{ m.content }}</div>
                    <p class="text-[10px] text-white/25 mt-1 px-1">assistant · {{ formatTime(m.created_at) }}<span v-if="m.memory_processed" class="ml-2 text-brand-400/60">✓ memory</span></p>
                  </div>
                </div>

                <!-- tool_call -->
                <div v-else-if="m.role === 'tool_call'">
                  <div class="border border-amber-500/20 bg-amber-500/5 rounded-xl px-3 py-2.5">
                    <div class="flex items-center gap-2 mb-2">
                      <span class="text-[10px] font-bold uppercase tracking-widest text-amber-400/70">tool call</span>
                      <span class="text-[10px] text-white/30">{{ formatTime(m.created_at) }}</span>
                    </div>
                    <pre v-if="m.content" class="text-xs text-amber-200/60 whitespace-pre-wrap break-all">{{ m.content }}</pre>
                    <pre v-if="m.actions_json" class="text-xs text-amber-200/50 whitespace-pre-wrap break-all mt-1">{{ json(m.actions_json) }}</pre>
                  </div>
                </div>

                <!-- tool_result -->
                <div v-else-if="m.role === 'tool_result'">
                  <div class="border border-emerald-500/20 bg-emerald-500/5 rounded-xl px-3 py-2.5">
                    <div class="flex items-center gap-2 mb-2">
                      <span class="text-[10px] font-bold uppercase tracking-widest text-emerald-400/70">tool result</span>
                      <span class="text-[10px] text-white/30">{{ formatTime(m.created_at) }}</span>
                    </div>
                    <pre class="text-xs text-emerald-200/60 whitespace-pre-wrap break-all">{{ m.content }}</pre>
                    <pre v-if="m.metadata_json" class="text-xs text-emerald-200/40 whitespace-pre-wrap break-all mt-1">{{ json(m.metadata_json) }}</pre>
                  </div>
                </div>

                <!-- system / other -->
                <div v-else>
                  <div class="border border-white/8 bg-white/3 rounded-xl px-3 py-2.5">
                    <div class="flex items-center gap-2 mb-1">
                      <span class="text-[10px] font-bold uppercase tracking-widest text-white/30">{{ m.role }}</span>
                      <span class="text-[10px] text-white/20">{{ formatTime(m.created_at) }}</span>
                    </div>
                    <pre class="text-xs text-white/40 whitespace-pre-wrap break-all">{{ m.content }}</pre>
                  </div>
                </div>

              </div>
              <p v-if="!detail.messages.length" class="text-center text-sm text-white/25 py-6">No messages</p>
            </template>
          </div>

        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAdminConversations } from '@/composables/admin/useAdminConversations'

const { detail, loadingDetail, fetchDetail } = useAdminConversations()

const route  = useRoute()
const router = useRouter()
const convId = ref<number | null>(Number(route.query.conv) || null)

const load = (id: number | null) => { if (id) fetchDetail(id) }

watch(() => route.query.conv, (val) => {
  convId.value = Number(val) || null
  load(convId.value)
})

onMounted(() => load(convId.value))

const close = () => {
  const q = { ...route.query }
  delete q.conv
  router.replace({ query: q })
}

const fmt = (n: number) =>
  n >= 1_000_000 ? `${(n / 1_000_000).toFixed(1)}M`
  : n >= 1_000   ? `${(n / 1_000).toFixed(1)}K`
  : String(n)

const formatDate = (d: string) =>
  new Date(d.includes('T') ? d : d.replace(' ', 'T') + 'Z')
    .toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })

const formatTime = (d: string) =>
  new Date(d.includes('T') ? d : d.replace(' ', 'T') + 'Z')
    .toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' })

const json = (v: unknown) => JSON.stringify(v, null, 2)
</script>
