<template>
  <AppDropdown ref="dropdown" align="left" class="w-full">
    <template #trigger>
      <div
        class="w-full flex items-center gap-2 bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm transition-all"
        :class="disabled ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer hover:border-white/20'"
      >
        <span class="flex-1 text-left truncate" :class="selectedLabel ? 'text-white/80' : 'text-white/25'">
          {{ selectedLabel ?? placeholder }}
        </span>
        <AppIcon name="ui/chevron-left" size="sm" class="shrink-0 text-white/30 -rotate-90" />
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
import { computed, ref } from 'vue'
import AppDropdown from '@/components/AppDropdown.vue'
import AppIcon     from '@/components/AppIcon.vue'

export interface SelectOption {
  value: string
  label: string
}

const props = withDefaults(defineProps<{
  modelValue:   string
  options:      SelectOption[]
  placeholder?: string
  disabled?:    boolean
}>(), {
  placeholder: 'Seleccionar…',
  disabled:    false,
})

const emit = defineEmits<{ 'update:modelValue': [value: string] }>()

const dropdown = ref<InstanceType<typeof AppDropdown> | null>(null)

const selectedLabel = computed(() =>
  props.options.find(o => o.value === props.modelValue)?.label ?? null,
)

const select = (value: string) => {
  emit('update:modelValue', value)
  dropdown.value?.close()
}
</script>
