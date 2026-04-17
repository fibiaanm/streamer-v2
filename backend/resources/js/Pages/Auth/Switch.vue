<template>
  <div class="dark">
    <PageBackground>
      <div class="min-h-screen flex items-center justify-center px-6">
        <div class="glass-auth w-full max-w-sm rounded-2xl p-8 space-y-6">

          <div class="space-y-1">
            <h1 class="text-2xl font-bold text-white">Selecciona una empresa</h1>
            <p class="text-sm text-white/50">¿Con cuál quieres continuar?</p>
          </div>

          <div v-if="loading" class="text-white/40 text-sm text-center py-4">
            Cargando...
          </div>

          <div v-else-if="enterprises.length" class="space-y-2">
            <button
              v-for="e in enterprises"
              :key="e.id"
              class="w-full text-left px-4 py-3 rounded-xl border border-white/10
                     bg-white/5 hover:bg-white/10 transition-colors cursor-pointer"
              @click="select(e.id)"
            >
              <p class="text-sm font-medium text-white">{{ e.name }}</p>
              <p class="text-xs text-white/40 capitalize">{{ e.type }}</p>
            </button>
          </div>

          <p v-else class="text-white/40 text-sm text-center">
            No tienes empresas asociadas.
          </p>

        </div>
      </div>
    </PageBackground>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import PageBackground from '@/components/PageBackground.vue'
import { useApi } from '@/lib/api'
import { useEnterpriseStore } from '@/stores/enterpriseStore'

interface EnterpriseOption {
  id: string
  name: string
  type: string
}

const api             = useApi()
const enterpriseStore = useEnterpriseStore()

const loading     = ref(true)
const enterprises = ref<EnterpriseOption[]>([])

onMounted(async () => {
  try {
    const res     = await api.get('/auth/profile')
    enterprises.value = res.data.data.enterprises
  } catch (err: any) {
    if (err.response?.status === 401) {
      window.location.href = '/login'
    }
  } finally {
    loading.value = false
  }
})

function select(id: string) {
  enterpriseStore.set(id)
  window.location.href = '/app'
}
</script>
