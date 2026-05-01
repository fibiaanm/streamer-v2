<template>
  <div class="space-y-5">
    <ConversationSearch />
    <ConversationDetail />
  </div>
</template>

<script setup lang="ts">
import ConversationSearch from '@/components/Admin/conversations/ConversationSearch.vue'
import ConversationDetail from '@/components/Admin/conversations/ConversationDetail.vue'
</script>
