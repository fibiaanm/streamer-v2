<template>
  <div class="dark">
    <PageBackground>
      <div class="relative min-h-screen">

        <!-- Background image (url passed from LoginPageController) -->
        <img
          :src="imageUrl"
          alt=""
          class="absolute inset-0 w-full h-full object-cover"
        />

        <!-- Right panel: absolute on md+, centered full-screen on mobile -->
        <div
          class="flex items-center justify-center min-h-screen px-6
                 w-full max-w-sm mx-auto
                 md:max-w-none md:mx-0 md:absolute md:inset-y-0 md:right-0 md:w-[480px] md:min-h-0 md:px-10"
        >
          <Transition name="auth-card" mode="out-in">
            <LoginPanel v-if="mode === 'login'" @switch="setMode" />
            <SignupPanel v-else-if="mode === 'signup'" @switch="setMode" />
            <ForgotPanel v-else @switch="setMode" />
          </Transition>
        </div>

      </div>
    </PageBackground>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'

defineProps<{ imageUrl: string }>()
import PageBackground from '@/components/PageBackground.vue'
import LoginPanel from './panels/LoginPanel.vue'
import SignupPanel from './panels/SignupPanel.vue'
import ForgotPanel from './panels/ForgotPanel.vue'

const route  = useRoute()
const router = useRouter()

const mode = computed(() => (route.query.mode as string) || 'login')

function setMode(newMode: string) {
  router.push({ query: newMode === 'login' ? {} : { mode: newMode } })
}
</script>

<style scoped>
.auth-card-leave-active {
  transition: opacity 150ms ease, transform 150ms ease;
}
.auth-card-enter-active {
  transition: opacity 220ms ease, transform 220ms ease;
  transition-delay: 60ms;
}
.auth-card-leave-to {
  opacity: 0;
  transform: translateY(12px) scale(0.98);
}
.auth-card-enter-from {
  opacity: 0;
  transform: translateY(-8px);
}
</style>
