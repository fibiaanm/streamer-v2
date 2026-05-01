<template>
  <div :class="{ dark: isDark }">
    <PageBackground>
      <div
        class="h-screen flex flex-col overflow-hidden transition-[padding-left] duration-200 ease-in-out"
        :style="{ paddingLeft: sidebarOpen && isDesktop ? '19.5rem' : '0' }"
      >
        <AppHeader>
          <template #left>
            <button
              v-if="$slots.sidebar"
              class="w-8 h-8 flex items-center justify-center rounded-xl text-white/50
                     hover:text-white/80 hover:bg-white/8 transition-colors cursor-pointer shrink-0"
              @click="sidebarOpen = !sidebarOpen"
            >
              <AppIcon name="ui/menu" size="sm" />
            </button>
            <slot name="header-left" />
          </template>
          <template v-if="$slots['header-center']" #center>
            <slot name="header-center" />
          </template>
          <template v-if="$slots['header-right']" #right>
            <slot name="header-right" />
          </template>
        </AppHeader>

        <div class="flex-1 -mt-[68px] overflow-hidden">
          <slot />
        </div>
      </div>
    </PageBackground>

    <!-- Sidebar: siempre fixed overlay, mismo DOM en mobile y desktop -->
    <template v-if="$slots.sidebar">
      <Teleport to="body">

        <!-- Backdrop — solo en mobile -->
        <Transition name="sb-fade">
          <div
            v-if="sidebarOpen && !isDesktop"
            class="fixed inset-0 z-[199] bg-black/30"
            @click="sidebarOpen = false"
          />
        </Transition>

        <!-- Panel -->
        <Transition name="sb-slide">
          <div
            v-if="sidebarOpen"
            class="fixed left-3 top-3 bottom-3 w-72 z-[200]
                   bg-white/6 backdrop-blur-xl rounded-2xl
                   flex flex-col overflow-hidden"
          >
            <slot name="sidebar" />
          </div>
        </Transition>

      </Teleport>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import PageBackground from '@/components/PageBackground.vue'
import AppHeader      from '@/components/AppHeader.vue'
import AppIcon        from '@/components/AppIcon.vue'
import { useTheme }   from '@/composables/core/useTheme'

const { isDark } = useTheme()

const isDesktop   = ref(typeof window !== 'undefined' && window.innerWidth >= 768)
const sidebarOpen = ref(isDesktop.value)

function onResize() {
  isDesktop.value = window.innerWidth >= 768
}

onMounted(() => window.addEventListener('resize', onResize))
onUnmounted(() => window.removeEventListener('resize', onResize))
</script>

<style scoped>
.sb-fade-enter-active,
.sb-fade-leave-active {
  transition: opacity 0.2s ease;
}
.sb-fade-enter-from,
.sb-fade-leave-to {
  opacity: 0;
}

.sb-slide-enter-active,
.sb-slide-leave-active {
  transition: transform 0.2s ease;
}
.sb-slide-enter-from,
.sb-slide-leave-to {
  transform: translateX(-110%);
}
</style>
