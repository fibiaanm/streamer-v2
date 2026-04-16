<template>
  <div :class="['transition-colors duration-300', isDark ? 'dark' : '']">

    <PageBackground>
      <div class="h-screen overflow-y-auto">

      <AppHeader>
        <template #left>
          <div class="flex items-center gap-3">
            <div class="w-6 h-6 rounded-md bg-brand-500 shadow-lg shadow-brand-500/40" />
            <span class="text-sm font-semibold text-slate-900 dark:text-white tracking-tight">
              streamer-v2-testin
              <span class="text-slate-400 dark:text-white/30 font-normal">/ design system</span>
            </span>
          </div>
        </template>
        <template #right>
          <button
            class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-medium
                   text-slate-600 dark:text-white/60 hover:text-slate-900 dark:hover:text-white
                   bg-slate-100 dark:bg-white/5 hover:bg-slate-200 dark:hover:bg-white/10
                   border border-slate-200 dark:border-white/8 transition-all cursor-pointer"
            @click="isDark = !isDark"
          >
            <span>{{ isDark ? '☀' : '🌙' }}</span>
            <span>{{ isDark ? 'Light mode' : 'Dark mode' }}</span>
          </button>
        </template>
      </AppHeader>

      <!-- ── Main content ───────────────────────────────────────────────────── -->
      <main class="relative max-w-7xl mx-auto px-6 py-14 space-y-20">

        <!-- ═════════════════════════════════════════════════════════════════ -->
        <!-- Primary Color Options                                           -->
        <!-- ═════════════════════════════════════════════════════════════════ -->
        <section>
          <SectionHeader label="Primary Color — Candidatos" />
          <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            <div
              v-for="opt in primaryOptions"
              :key="opt.name"
              class="rounded-2xl p-5 space-y-4
                     bg-white/5 border border-white/8"
            >
              <!-- Header -->
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-semibold text-white/90">{{ opt.name }}</p>
                  <p class="text-xs font-mono text-white/30 mt-0.5">{{ opt.shades[5].hex }}</p>
                </div>
                <div
                  class="w-9 h-9 rounded-xl ring-1 ring-white/10"
                  :style="{ background: opt.shades[5].hex }"
                />
              </div>

              <!-- Palette strip -->
              <div class="grid grid-cols-11 gap-1">
                <div
                  v-for="s in opt.shades"
                  :key="s.shade"
                  class="aspect-square rounded"
                  :style="{ background: s.hex }"
                  :title="s.shade + ' · ' + s.hex"
                />
              </div>

              <!-- Preview -->
              <div class="flex items-center gap-2 pt-1">
                <!-- Button -->
                <button
                  class="px-3 py-1.5 rounded-lg text-white text-xs font-medium
                         transition-all cursor-pointer"
                  :style="{
                    background: opt.shades[6].hex,
                    boxShadow: `0 4px 14px ${opt.shades[5].hex}40`,
                  }"
                >
                  Primary
                </button>
                <!-- Badge -->
                <span
                  class="px-2 py-1 rounded-full text-xs font-medium"
                  :style="{
                    background: opt.shades[5].hex + '20',
                    color: opt.shades[3].hex,
                    border: `1px solid ${opt.shades[5].hex}30`,
                  }"
                >
                  Badge
                </span>
                <!-- Focus ring -->
                <div class="flex items-center gap-1.5">
                  <div
                    class="w-6 h-6 rounded-md bg-white/10 ring-2"
                    :style="{ ringColor: opt.shades[4].hex, outline: `2px solid ${opt.shades[4].hex}` }"
                  />
                  <span class="text-[10px] text-white/30">focus</span>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- ═════════════════════════════════════════════════════════════════ -->
        <!-- Color Palette                                                    -->
        <!-- ═════════════════════════════════════════════════════════════════ -->
        <section>
          <SectionHeader label="Color Palette" />

          <PaletteRow
            name="Brand"
            subtitle="sky — UI primario, CTAs, focus rings"
            :shades="brandPalette"
          />
          <PaletteRow
            name="Live"
            subtitle="cyan — indicadores activos, streaming"
            :shades="livePalette"
          />
          <PaletteRow
            name="Neutral"
            subtitle="slate — texto, bordes, superficies"
            :shades="slatePalette"
          />

          <!-- Semantic -->
          <div class="mt-8">
            <p class="text-xs font-semibold uppercase tracking-widest
                      text-slate-400 dark:text-white/30 mb-4">
              Semantic
            </p>
            <div class="flex flex-wrap gap-4">
              <SemanticSwatch label="Success" hex="#10b981" />
              <SemanticSwatch label="Warning" hex="#f59e0b" />
              <SemanticSwatch label="Danger"  hex="#f43f5e" />
              <SemanticSwatch label="Info"    hex="#3b82f6" />
            </div>
          </div>
        </section>

        <!-- ═════════════════════════════════════════════════════════════════ -->
        <!-- Typography                                                       -->
        <!-- ═════════════════════════════════════════════════════════════════ -->
        <section>
          <SectionHeader label="Typography" />
          <div class="glass-light dark:glass rounded-2xl p-8 space-y-6">
            <div
              v-for="t in typeScale"
              :key="t.label"
              class="flex items-baseline gap-6
                     border-b border-slate-200/60 dark:border-white/6
                     pb-5 last:border-0 last:pb-0"
            >
              <span class="w-20 shrink-0 text-xs font-mono
                           text-slate-400 dark:text-white/30">
                {{ t.label }}
              </span>
              <span :class="t.class">{{ t.sample }}</span>
            </div>
          </div>
        </section>

        <!-- ═════════════════════════════════════════════════════════════════ -->
        <!-- Elements                                                         -->
        <!-- ═════════════════════════════════════════════════════════════════ -->
        <section class="pb-20">
          <SectionHeader label="Elements" />
          <div class="glass-light dark:glass rounded-2xl p-8 space-y-10">

            <!-- Buttons -->
            <div>
              <p class="text-xs font-semibold uppercase tracking-widest
                        text-slate-400 dark:text-white/30 mb-4">
                Buttons
              </p>
              <!-- Variants -->
              <div class="flex flex-wrap items-center gap-3">
                <AppButton variant="primary">Primary</AppButton>
                <AppButton variant="secondary">Secondary</AppButton>
                <AppButton variant="ghost">Ghost</AppButton>
                <AppButton variant="danger">Danger</AppButton>
                <AppButton variant="live">Live</AppButton>
              </div>
              <!-- Sizes -->
              <div class="flex flex-wrap items-center gap-3 mt-4">
                <AppButton variant="primary" size="xs">XSmall</AppButton>
                <AppButton variant="primary" size="sm">Small</AppButton>
                <AppButton variant="primary" size="md">Medium</AppButton>
                <AppButton variant="primary" size="lg">Large</AppButton>
                <AppButton variant="primary" size="xl">XLarge</AppButton>
              </div>
              <!-- States -->
              <div class="flex flex-wrap items-center gap-3 mt-4">
                <AppButton variant="primary" loading>Guardando...</AppButton>
                <AppButton variant="secondary" loading>Cargando</AppButton>
                <AppButton variant="danger" loading>Eliminando</AppButton>
                <AppButton variant="primary" disabled>Desactivado</AppButton>
              </div>
              <!-- Icons -->
              <div class="flex flex-wrap items-center gap-3 mt-4">
                <AppButton variant="primary" icon="ui/check">Confirmar</AppButton>
                <AppButton variant="danger" icon="ui/x">Eliminar</AppButton>
                <AppButton variant="secondary" icon="ui/eye" icon-position="right">Ver más</AppButton>
                <AppButton variant="ghost" icon="ui/eye-off">Ocultar</AppButton>
              </div>
            </div>

            <!-- Inputs -->
            <div>
              <p class="text-xs font-semibold uppercase tracking-widest
                        text-slate-400 dark:text-white/30 mb-4">
                Inputs
              </p>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-2xl">
                <!-- Floating label -->
                <AppInput v-model="inputVal" label="Email" placeholder="example@streamer.com" />
                <!-- Hint -->
                <AppInput label="Username" hint="Letters and numbers only." />
                <!-- Error -->
                <AppInput label="Password" type="password" error="Too short, minimum 8 chars." variant="error" />
                <!-- Success -->
                <AppInput label="Invite code" variant="success" model-value="STREAM-2025" />
                <!-- No label, with trailing icon -->
                <AppInput placeholder="Search rooms…">
                  <template #trailing>
                    <svg class="w-4 h-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                      <circle cx="6.5" cy="6.5" r="4.5" />
                      <path d="M10.5 10.5l3 3" stroke-linecap="round" />
                    </svg>
                  </template>
                </AppInput>
                <!-- Disabled -->
                <AppInput label="Workspace" model-value="streamer-v2" disabled />
              </div>
              <!-- Size row -->
              <div class="flex flex-col sm:flex-row items-end gap-3 mt-4 max-w-2xl">
                <AppInput label="Small" size="sm" class="flex-1" />
                <AppInput label="Medium" size="md" class="flex-1" />
                <AppInput label="Large" size="lg" class="flex-1" />
              </div>
            </div>

            <!-- Badges -->
            <div>
              <p class="text-xs font-semibold uppercase tracking-widest
                        text-slate-400 dark:text-white/30 mb-4">
                Badges
              </p>
              <div class="flex flex-wrap gap-2">
                <AppBadge variant="brand">Brand</AppBadge>
                <AppBadge variant="success">Success</AppBadge>
                <AppBadge variant="warning">Warning</AppBadge>
                <AppBadge variant="danger">Danger</AppBadge>
                <AppBadge variant="live">En vivo</AppBadge>
                <AppBadge variant="neutral">Neutral</AppBadge>
              </div>
            </div>

            <!-- Tags -->
            <div>
              <p class="text-xs font-semibold uppercase tracking-widest
                        text-slate-400 dark:text-white/30 mb-4">
                Tags
              </p>
              <div class="flex flex-wrap gap-2">
                <AppTag variant="brand">Design</AppTag>
                <AppTag variant="success">Active</AppTag>
                <AppTag variant="warning">Pending</AppTag>
                <AppTag variant="danger">Blocked</AppTag>
                <AppTag variant="neutral">Draft</AppTag>
                <AppTag variant="brand" removable>Removable</AppTag>
                <AppTag variant="neutral" removable>Tag</AppTag>
              </div>
            </div>

          </div>
        </section>

      </main>
      </div>
    </PageBackground>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import PageBackground from '@/components/PageBackground.vue'
import AppHeader from '@/components/AppHeader.vue'
import AppButton from '@/components/AppButton.vue'
import AppBadge from '@/components/AppBadge.vue'
import AppTag from '@/components/AppTag.vue'
import AppInput from '@/components/AppInput.vue'
import SectionHeader from './DesignTest/SectionHeader.vue'
import PaletteRow from './DesignTest/PaletteRow.vue'
import SemanticSwatch from './DesignTest/SemanticSwatch.vue'

// ─── Dark/light toggle ────────────────────────────────────────────────────────
const isDark   = ref(true)
const inputVal = ref('')

// ─── Color palettes ───────────────────────────────────────────────────────────
interface ColorShade  { shade: number; hex: string }
interface PrimaryOption { name: string; shades: ColorShade[] }

// Candidatos primary — se muestran en la sección de decisión
const primaryOptions: PrimaryOption[] = [
  {
    name: 'Indigo',
    shades: [
      { shade: 50, hex: '#eef2ff' }, { shade: 100, hex: '#e0e7ff' },
      { shade: 200, hex: '#c7d2fe' }, { shade: 300, hex: '#a5b4fc' },
      { shade: 400, hex: '#818cf8' }, { shade: 500, hex: '#6366f1' },
      { shade: 600, hex: '#4f46e5' }, { shade: 700, hex: '#4338ca' },
      { shade: 800, hex: '#3730a3' }, { shade: 900, hex: '#312e81' },
      { shade: 950, hex: '#1e1b4b' },
    ],
  },
  {
    name: 'Blue',
    shades: [
      { shade: 50, hex: '#eff6ff' }, { shade: 100, hex: '#dbeafe' },
      { shade: 200, hex: '#bfdbfe' }, { shade: 300, hex: '#93c5fd' },
      { shade: 400, hex: '#60a5fa' }, { shade: 500, hex: '#3b82f6' },
      { shade: 600, hex: '#2563eb' }, { shade: 700, hex: '#1d4ed8' },
      { shade: 800, hex: '#1e40af' }, { shade: 900, hex: '#1e3a8a' },
      { shade: 950, hex: '#172554' },
    ],
  },
  {
    name: 'Sky',
    shades: [
      { shade: 50, hex: '#f0f9ff' }, { shade: 100, hex: '#e0f2fe' },
      { shade: 200, hex: '#bae6fd' }, { shade: 300, hex: '#7dd3fc' },
      { shade: 400, hex: '#38bdf8' }, { shade: 500, hex: '#0ea5e9' },
      { shade: 600, hex: '#0284c7' }, { shade: 700, hex: '#0369a1' },
      { shade: 800, hex: '#075985' }, { shade: 900, hex: '#0c4a6e' },
      { shade: 950, hex: '#082f49' },
    ],
  },
  {
    name: 'Rose',
    shades: [
      { shade: 50, hex: '#fff1f2' }, { shade: 100, hex: '#ffe4e6' },
      { shade: 200, hex: '#fecdd3' }, { shade: 300, hex: '#fda4af' },
      { shade: 400, hex: '#fb7185' }, { shade: 500, hex: '#f43f5e' },
      { shade: 600, hex: '#e11d48' }, { shade: 700, hex: '#be123c' },
      { shade: 800, hex: '#9f1239' }, { shade: 900, hex: '#881337' },
      { shade: 950, hex: '#4c0519' },
    ],
  },
  {
    name: 'Orange',
    shades: [
      { shade: 50, hex: '#fff7ed' }, { shade: 100, hex: '#ffedd5' },
      { shade: 200, hex: '#fed7aa' }, { shade: 300, hex: '#fdba74' },
      { shade: 400, hex: '#fb923c' }, { shade: 500, hex: '#f97316' },
      { shade: 600, hex: '#ea580c' }, { shade: 700, hex: '#c2410c' },
      { shade: 800, hex: '#9a3412' }, { shade: 900, hex: '#7c2d12' },
      { shade: 950, hex: '#431407' },
    ],
  },
  {
    name: 'Teal',
    shades: [
      { shade: 50, hex: '#f0fdfa' }, { shade: 100, hex: '#ccfbf1' },
      { shade: 200, hex: '#99f6e4' }, { shade: 300, hex: '#5eead4' },
      { shade: 400, hex: '#2dd4bf' }, { shade: 500, hex: '#14b8a6' },
      { shade: 600, hex: '#0d9488' }, { shade: 700, hex: '#0f766e' },
      { shade: 800, hex: '#115e59' }, { shade: 900, hex: '#134e4a' },
      { shade: 950, hex: '#042f2e' },
    ],
  },
]

const brandPalette: ColorShade[] = [
  { shade: 50,  hex: '#f0f9ff' }, { shade: 100, hex: '#e0f2fe' },
  { shade: 200, hex: '#bae6fd' }, { shade: 300, hex: '#7dd3fc' },
  { shade: 400, hex: '#38bdf8' }, { shade: 500, hex: '#0ea5e9' },
  { shade: 600, hex: '#0284c7' }, { shade: 700, hex: '#0369a1' },
  { shade: 800, hex: '#075985' }, { shade: 900, hex: '#0c4a6e' },
  { shade: 950, hex: '#082f49' },
]

const livePalette: ColorShade[] = [
  { shade: 50,  hex: '#ecfeff' }, { shade: 100, hex: '#cffafe' },
  { shade: 200, hex: '#a5f3fc' }, { shade: 300, hex: '#67e8f9' },
  { shade: 400, hex: '#22d3ee' }, { shade: 500, hex: '#06b6d4' },
  { shade: 600, hex: '#0891b2' }, { shade: 700, hex: '#0e7490' },
  { shade: 800, hex: '#155e75' }, { shade: 900, hex: '#164e63' },
  { shade: 950, hex: '#083344' },
]

const slatePalette: ColorShade[] = [
  { shade: 50,  hex: '#f8fafc' }, { shade: 100, hex: '#f1f5f9' },
  { shade: 200, hex: '#e2e8f0' }, { shade: 300, hex: '#cbd5e1' },
  { shade: 400, hex: '#94a3b8' }, { shade: 500, hex: '#64748b' },
  { shade: 600, hex: '#475569' }, { shade: 700, hex: '#334155' },
  { shade: 800, hex: '#1e293b' }, { shade: 900, hex: '#0f172a' },
  { shade: 950, hex: '#020617' },
]

// ─── Type scale ───────────────────────────────────────────────────────────────
const typeScale = [
  { label: 'Display', class: 'text-5xl font-bold tracking-tight text-slate-900 dark:text-white leading-tight',   sample: 'Streaming colaborativo' },
  { label: 'H1',      class: 'text-4xl font-bold tracking-tight text-slate-900 dark:text-white leading-tight',   sample: 'Tu workspace en vivo' },
  { label: 'H2',      class: 'text-3xl font-semibold text-slate-900 dark:text-white',                             sample: 'Salas activas' },
  { label: 'H3',      class: 'text-2xl font-semibold text-slate-800 dark:text-white/90',                          sample: 'Participantes' },
  { label: 'H4',      class: 'text-xl font-medium text-slate-800 dark:text-white/80',                             sample: 'Configuración de sala' },
  { label: 'Body',    class: 'text-base text-slate-700 dark:text-white/70',                                       sample: 'Únete a cualquier sala de tu workspace y colabora en tiempo real con tu equipo.' },
  { label: 'Small',   class: 'text-sm text-slate-500 dark:text-white/40',                                         sample: 'Última actividad hace 2 minutos · 4 participantes' },
  { label: 'Mono',    class: 'text-sm font-mono text-live-600 dark:text-live-400',                                sample: 'ws://streamer.local:3000/socket.io' },
]


</script>
