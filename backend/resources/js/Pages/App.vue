<template>
  <RouterView />
</template>

<script setup lang="ts">
import { RouterView } from 'vue-router'
import { useSession } from '@/composables/core/useSession'
import { useSocket } from '@/composables/core/useSocket'

const { accessToken } = useSession()
const { connect }     = useSocket()

if (accessToken.value) {
  connect(accessToken.value)
}
</script>
