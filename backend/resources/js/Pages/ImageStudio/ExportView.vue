<template>
  <div class="flex flex-col h-full overflow-hidden">
    <div class="flex flex-1 overflow-hidden">
      <ImageGrid
        :items="items"
        :active-item-id="activeItemId"
        @select="emit('select', $event)"
        @rename="(id, name) => emit('rename', id, name)"
      />
      <div class="flex-1 flex flex-col overflow-hidden">
        <ExportConfigPanel
          :item="activeItem"
          @add-config="emit('addConfig')"
          @remove-config="(id) => emit('removeConfig', id)"
          @update-config="(id, cfg) => emit('updateConfig', id, cfg)"
        />
      </div>
    </div>

    <!-- Footer: zip name + summary + download -->
    <div class="shrink-0 border-t border-white/8 px-5 py-3 flex items-center gap-3">
      <!-- ZIP name -->
      <div class="flex items-center gap-2 min-w-0">
        <span class="text-[11px] text-white/30 shrink-0">ZIP</span>
        <div class="flex items-center gap-1 bg-white/5 border border-white/10 rounded-lg px-2.5 py-1.5">
          <input
            :value="zipName"
            class="text-xs text-white/70 bg-transparent outline-none w-32"
            placeholder="mis-imagenes"
            @input="emit('update:zipName', ($event.target as HTMLInputElement).value)"
          />
          <span class="text-xs text-white/25 shrink-0">.zip</span>
        </div>
      </div>

      <!-- Summary -->
      <p class="text-xs text-white/25 flex-1 min-w-0 truncate">
        <span class="text-white/50 font-medium">{{ items.length }}</span>
        {{ items.length === 1 ? 'imagen' : 'imágenes' }}
        <span class="mx-1.5 text-white/15">·</span>
        <span class="text-white/50 font-medium">{{ totalOutputs }}</span>
        outputs
      </p>

      <!-- Download button -->
      <AppButton
        variant="primary"
        size="sm"
        :disabled="totalOutputs === 0"
        icon="ui/download"
        @click="emit('downloadZip')"
      >
        Descargar ZIP
      </AppButton>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { ImageItem, ExportConfig } from '@/types/imageStudio'
import ImageGrid         from './ImageGrid.vue'
import ExportConfigPanel from './ExportConfigPanel.vue'
import AppButton         from '@/components/AppButton.vue'

const props = defineProps<{
  items: ImageItem[]
  activeItemId: string | null
  zipName: string
}>()

const emit = defineEmits<{
  select: [id: string]
  rename: [id: string, name: string]
  addConfig: []
  removeConfig: [configId: string]
  updateConfig: [configId: string, config: ExportConfig]
  downloadZip: []
  'update:zipName': [name: string]
}>()

const activeItem = computed(() =>
  props.items.find(i => i.id === props.activeItemId) ?? null
)

const totalOutputs = computed(() =>
  props.items.reduce((sum, item) => sum + item.exportConfigs.length, 0)
)
</script>
