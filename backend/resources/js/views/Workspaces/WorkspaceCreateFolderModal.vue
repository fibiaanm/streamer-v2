<template>
  <AppModal :is-open="isOpen" max-width="sm" @close="onClose">
    <template #header>
      <div class="flex items-center gap-2.5">
        <div class="w-7 h-7 rounded-lg bg-white/6 flex items-center justify-center">
          <AppIcon name="ui/grid" size="sm" class="text-white/40" />
        </div>
        <span class="text-sm font-semibold text-white/80">Nueva carpeta</span>
      </div>
    </template>

    <div class="flex flex-col gap-4">
      <p v-if="parentName" class="text-xs text-white/35 -mt-1">
        Dentro de <span class="font-medium text-white/55">{{ parentName }}</span>
      </p>
      <AppInput
        v-model="name"
        label="Nombre"
        autofocus
        @keydown.enter="onSubmit"
      />
    </div>

    <template #footer>
      <AppButton variant="ghost" size="sm" @click="onClose">Cancelar</AppButton>
      <AppButton variant="primary" size="sm" :disabled="!name.trim()" @click="onSubmit">
        Crear carpeta
      </AppButton>
    </template>
  </AppModal>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import AppModal  from '@/components/AppModal.vue'
import AppInput  from '@/components/AppInput.vue'
import AppButton from '@/components/AppButton.vue'
import AppIcon   from '@/components/AppIcon.vue'

const props = defineProps<{
  isOpen:      boolean
  parentName?: string
}>()

const emit = defineEmits<{
  close:   []
  created: [name: string]
}>()

const name = ref('')

watch(() => props.isOpen, (open) => {
  if (open) name.value = ''
})

function onClose() {
  emit('close')
}

function onSubmit() {
  const trimmed = name.value.trim()
  if (!trimmed) return
  emit('created', trimmed)
  onClose()
}
</script>
