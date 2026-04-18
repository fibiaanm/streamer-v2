<template>
  <RouterView v-if="ready" />
  <AppToastContainer />
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { RouterView } from 'vue-router'
import AppToastContainer from '@/components/AppToastContainer.vue'
import { useSession }        from '@/composables/core/useSession'
import { useSocket }         from '@/composables/core/useSocket'
import { useEnterpriseSync } from '@/composables/core/useEnterpriseSync'
import { useUserSync }       from '@/composables/core/useUserSync'
import { useApi }            from '@/lib/api'
import type { SessionUser }  from '@/types'

const ready       = ref(false)
const session     = useSession()
const api         = useApi()
const { connect } = useSocket()
useEnterpriseSync()
useUserSync()

onMounted(async () => {
  try {
    const res = await api.get('/auth/me')
    session.setUser(res.data.data as SessionUser)
    if (res.data.data.enterprise?.role !== 'guest') connect()
  } catch (err: any) {
    if (err.response?.status === 403) {
      window.location.href = '/switch'
      return
    }
  }
  ready.value = true
})
</script>
