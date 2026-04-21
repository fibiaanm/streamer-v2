<template>
  <div class="flex flex-col p-8 gap-8">

    <div class="space-y-1">
      <h2 class="text-sm font-semibold text-white/70">General</h2>
      <p class="text-xs text-white/30">Información de tu perfil de usuario</p>
    </div>

    <!-- Avatar -->
    <div class="flex items-center gap-5">
      <div class="relative">
        <UserAvatar v-if="user" :user="user" size="xl" />
        <button
          v-if="user?.avatar_url.jpeg"
          class="absolute -top-1 -right-1 w-5 h-5 rounded-full bg-red-500/80 hover:bg-red-500 flex items-center justify-center transition-colors"
          :disabled="deletingAvatar"
          title="Eliminar foto"
          @click="handleDeleteAvatar"
        >
          <AppIcon name="ui/x" class="w-3 h-3 text-white" />
        </button>
      </div>

      <div class="space-y-1">
        <label
          class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white/8 hover:bg-white/12 text-xs text-white/70 cursor-pointer transition-colors"
        >
          <AppIcon name="ui/image" class="w-3.5 h-3.5" />
          <span>{{ uploadingAvatar ? 'Subiendo…' : 'Cambiar foto' }}</span>
          <input
            ref="fileInput"
            type="file"
            accept="image/*"
            class="hidden"
            @change="handleAvatarChange"
          />
        </label>
        <p class="text-xs text-white/25">JPG, PNG o WebP · máx. 2 MB</p>
      </div>
    </div>

    <!-- Nombre -->
    <div class="max-w-sm space-y-4">
      <AppInput v-model="name" label="Nombre" />

      <AppInput :model-value="user?.email ?? ''" label="Email" :readonly="true" />

      <AppButton
        size="sm"
        :loading="savingName"
        :disabled="!canSaveName"
        @click="handleSaveName"
      >
        Guardar
      </AppButton>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useSession }     from '@/composables/core/useSession'
import { useToasts }      from '@/composables/core/useToasts'
import { useProfileApi }  from '@/composables/api/useProfileApi'
import AppInput   from '@/components/AppInput.vue'
import AppButton  from '@/components/AppButton.vue'
import AppIcon    from '@/components/AppIcon.vue'
import UserAvatar from '@/components/UserAvatar.vue'

const { user }          = useSession()
const { add: addToast } = useToasts()
const { updateProfile, uploadAvatar, deleteAvatar } = useProfileApi()

const name         = ref(user.value?.name ?? '')
const originalName = ref(user.value?.name ?? '')
const savingName   = ref(false)
const uploadingAvatar = ref(false)
const deletingAvatar  = ref(false)

const canSaveName = computed(
  () => name.value.trim() !== '' && name.value !== originalName.value
)

watch(() => user.value?.name, (val) => {
  if (val) { name.value = val; originalName.value = val }
})

async function handleSaveName() {
  savingName.value = true
  try {
    const data = await updateProfile(name.value.trim())
    if (user.value) user.value.name = data.name
    originalName.value = data.name
    addToast({ type: 'success', title: 'Nombre actualizado', duration: 3000 })
  } catch {
    addToast({ type: 'error', title: 'No se pudo guardar', message: 'Intenta de nuevo.', duration: 5000 })
  } finally {
    savingName.value = false
  }
}

async function handleAvatarChange(event: Event) {
  const file = (event.target as HTMLInputElement).files?.[0]
  if (!file) return
  uploadingAvatar.value = true
  try {
    const url = await uploadAvatar(file)
    if (user.value) user.value.avatar_url = url
    addToast({ type: 'success', title: 'Foto actualizada', duration: 3000 })
  } catch {
    addToast({ type: 'error', title: 'No se pudo subir la foto', message: 'Intenta de nuevo.', duration: 5000 })
  } finally {
    uploadingAvatar.value = false
    ;(event.target as HTMLInputElement).value = ''
  }
}

async function handleDeleteAvatar() {
  deletingAvatar.value = true
  try {
    await deleteAvatar()
    if (user.value) user.value.avatar_url = null
    addToast({ type: 'success', title: 'Foto eliminada', duration: 3000 })
  } catch {
    addToast({ type: 'error', title: 'No se pudo eliminar la foto', duration: 5000 })
  } finally {
    deletingAvatar.value = false
  }
}
</script>
