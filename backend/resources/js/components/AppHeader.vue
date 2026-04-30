<template>
  <header class="w-full px-4 pt-3 shrink-0">
    <div class="glass-light dark:glass rounded-2xl px-5 h-14
                flex items-center justify-between gap-4">
      <div class="flex-1 flex items-center justify-start gap-2">
        <slot name="left" />
      </div>
      <div class="flex items-center justify-center">
        <slot name="center" />
      </div>
      <div class="flex-1 flex items-center justify-end">
        <slot name="right" />
      </div>
    </div>
  </header>
</template>
