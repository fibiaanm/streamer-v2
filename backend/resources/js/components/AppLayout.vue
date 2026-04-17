<template>
  <div class="dark">
    <PageBackground>
      <div class="h-screen overflow-y-auto">

        <AppHeader>
          <template v-if="$slots['header-left']" #left>
            <slot name="header-left" />
          </template>
          <template v-if="$slots['header-center']" #center>
            <slot name="header-center" />
          </template>
          <template v-if="$slots['header-right']" #right>
            <slot name="header-right" />
          </template>
        </AppHeader>

        <slot />

      </div>
    </PageBackground>
  </div>
</template>

<script setup lang="ts">
import PageBackground from '@/components/PageBackground.vue'
import AppHeader      from '@/components/AppHeader.vue'
</script>
