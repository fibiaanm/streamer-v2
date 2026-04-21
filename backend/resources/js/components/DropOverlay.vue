<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-[300] pointer-events-none flex items-center justify-center">
      <div class="absolute inset-0 bg-brand-500/6 backdrop-blur-[2px]" />
      <div class="absolute inset-4 rounded-3xl border-2 border-dashed border-brand-400/50 transition-all" />
      <div class="relative glass-brand rounded-2xl px-10 py-7 text-center space-y-3 shadow-glass-brand">
        <div class="w-14 h-14 rounded-2xl bg-brand-500/15 border border-brand-400/25 flex items-center justify-center mx-auto">
          <svg class="w-7 h-7 text-brand-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M12 16V4m0 0L8 8m4-4l4 4" stroke-linecap="round" stroke-linejoin="round" />
            <path d="M4 20h16" stroke-linecap="round" />
          </svg>
        </div>
        <div>
          <p class="text-sm font-semibold text-white/90">Suelta las imágenes aquí</p>
          <p class="text-xs text-white/35 mt-1">JPG · PNG · WebP · GIF · HEIC · ZIP</p>
        </div>
      </div>
    </div>
  </Teleport>
</template>
