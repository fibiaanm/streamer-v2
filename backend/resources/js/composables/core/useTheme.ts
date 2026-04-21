import { ref, watch } from 'vue'

const isDark = ref(localStorage.getItem('theme') !== 'light')

function applyDark(val: boolean) {
  document.documentElement.classList.toggle('dark', val)
  localStorage.setItem('theme', val ? 'dark' : 'light')
}

// Apply immediately so Teleport'd modals (outside AppLayout) inherit the class
applyDark(isDark.value)

watch(isDark, applyDark)

export function useTheme() {
  return {
    isDark,
    toggle: () => { isDark.value = !isDark.value },
  }
}
