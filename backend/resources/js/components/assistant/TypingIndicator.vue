<template>
  <div class="flex justify-start px-4 py-1">
    <div class="rounded-2xl rounded-bl-md px-4 py-3 glass flex items-center gap-1">
      <span
        v-for="i in 3"
        :key="i"
        class="w-1.5 h-1.5 rounded-full bg-white/40 animate-bounce"
        :style="{ animationDelay: `${(i - 1) * 0.15}s` }"
      />
    </div>
  </div>
</template>
