<script setup lang="ts">
defineProps<{ moon: boolean }>()
</script>

<template>
  <svg
    viewBox="0 0 24 24"
    width="16"
    height="16"
    fill="none"
    stroke="currentColor"
    stroke-width="1.75"
    stroke-linecap="round"
    stroke-linejoin="round"
    :class="{ 'is-moon': moon }"
    aria-hidden="true"
  >
    <!-- Sun -->
    <g class="sun">
      <circle cx="12" cy="12" r="4.5" />
      <line x1="12"    y1="2"     x2="12"    y2="4.5"   />
      <line x1="12"    y1="19.5"  x2="12"    y2="22"    />
      <line x1="2"     y1="12"    x2="4.5"   y2="12"    />
      <line x1="19.5"  y1="12"    x2="22"    y2="12"    />
      <line x1="5.64"  y1="5.64"  x2="7.41"  y2="7.41"  />
      <line x1="16.59" y1="16.59" x2="18.36" y2="18.36" />
      <line x1="18.36" y1="5.64"  x2="16.59" y2="7.41"  />
      <line x1="5.64"  y1="18.36" x2="7.41"  y2="16.59" />
    </g>

    <!-- Moon -->
    <path
      class="moon"
      d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"
    />
  </svg>
</template>

<style scoped>
.sun {
  transform-origin: 12px 12px;
  transition: opacity 0.3s ease, transform 0.4s ease;
}

.is-moon .sun {
  opacity: 0;
  transform: rotate(-30deg) scale(0.5);
}

.moon {
  transform-origin: 12px 12px;
  opacity: 0;
  transform: rotate(40deg) scale(0.5);
  transition: opacity 0.3s ease, transform 0.4s ease;
}

.is-moon .moon {
  opacity: 1;
  transform: rotate(0deg) scale(1);
}
</style>
