<template>
  <AppDropdown ref="dropdown" align="left" class="w-full" @update:open="isOpen = $event">
    <template #trigger>
      <div
        class="w-full flex items-center gap-2 bg-white/5 border rounded-lg px-3 transition-all"
        :class="[
          disabled ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer',
          isOpen
            ? 'border-brand-400/40 ring-2 ring-brand-400/15'
            : disabled ? 'border-white/10' : 'border-white/10 hover:border-white/20',
          label ? 'py-2.5 min-h-[52px]' : 'py-2',
        ]"
      >
        <!-- Label + value column -->
        <div class="flex-1 flex flex-col justify-center min-w-0" :class="label ? 'gap-0.5' : ''">
          <span
            v-if="label"
            class="text-[10px] font-medium leading-none transition-colors"
            :class="isOpen ? 'text-brand-400' : 'text-white/40'"
          >
            {{ label }}
          </span>
          <span
            class="text-sm truncate leading-snug"
            :class="selectedLabel ? 'text-white/80' : 'text-white/25'"
          >
            {{ selectedLabel ?? placeholder }}
          </span>
        </div>

        <!-- Chevron centered to full container -->
        <AppIcon
          name="ui/chevron-left"
          size="sm"
          class="shrink-0 text-white/30 -rotate-90 transition-transform"
          :class="isOpen ? 'rotate-90' : ''"
        />
      </div>
    </template>

    <div class="py-0.5">
      <button
        v-for="opt in options"
        :key="opt.value"
        class="w-full flex items-center gap-3 px-4 py-2 text-sm transition-colors cursor-pointer text-left"
        :class="opt.value === modelValue ? 'text-white/90 bg-white/5' : 'text-white/60 hover:text-white/90 hover:bg-white/5'"
        @click="select(opt.value)"
      >
        <span class="flex-1 truncate">{{ opt.label }}</span>
        <AppIcon v-if="opt.value === modelValue" name="ui/check" size="sm" class="shrink-0 text-white/40" />
      </button>
    </div>
  </AppDropdown>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import AppDropdown from '@/components/AppDropdown.vue'
import AppIcon     from '@/components/AppIcon.vue'

export interface SelectOption {
  value: string
  label: string
}

const props = withDefaults(defineProps<{
  modelValue:   string
  options:      SelectOption[]
  label?:       string
  placeholder?: string
  disabled?:    boolean
}>(), {
  placeholder: 'Seleccionar…',
  disabled:    false,
})

const emit = defineEmits<{ 'update:modelValue': [value: string] }>()

const dropdown = ref<InstanceType<typeof AppDropdown> | null>(null)
const isOpen   = ref(false)

const selectedLabel = computed(() =>
  props.options.find(o => o.value === props.modelValue)?.label ?? null,
)

const select = (value: string) => {
  emit('update:modelValue', value)
  dropdown.value?.close()
}
</script>
