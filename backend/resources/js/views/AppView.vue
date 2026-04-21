<template>
  <AppLayout>
    <template #header-left>
      <AppMenuSwitcher />
    </template>
    <template #header-right>
      <UserMenu />
    </template>
  </AppLayout>
</template>

<script setup lang="ts">
import AppLayout       from '@/components/AppLayout.vue'
import AppMenuSwitcher from '@/components/AppMenuSwitcher.vue'
import UserMenu        from '@/components/UserMenu.vue'
</script>
