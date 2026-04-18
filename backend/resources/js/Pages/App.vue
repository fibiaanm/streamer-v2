<template>
  <RouterView v-if="ready" />
  <AppToastContainer />
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { RouterView } from 'vue-router'
import AppToastContainer from '@/components/AppToastContainer.vue'
import { useSession } from '@/composables/core/useSession'
import { useEnterpriseStore } from '@/stores/enterpriseStore'
import { useSocket } from '@/composables/core/useSocket'
import { useEnterpriseSync } from '@/composables/core/useEnterpriseSync'
import { useUserSync } from '@/composables/core/useUserSync'
import { useApi } from '@/lib/api'
import type { SessionUser } from '@/types'

const ready           = ref(false)
const session         = useSession()
const enterpriseStore = useEnterpriseStore()
const api             = useApi()
const { connect } = useSocket()
useEnterpriseSync()
useUserSync()

onMounted(async () => {
  if (!enterpriseStore.activeEnterpriseId) {
    window.location.href = '/switch'
    return
  }

  try {
    const res = await api.get('/auth/me')
    session.setUser(res.data.data as SessionUser)
    connect()
    ready.value = true
  } catch (err: any) {
    if (err.response?.status === 403) {
      window.location.href = '/switch'
    } else {
      ready.value = true
    }
  }
})
</script>
