import { ref, watch } from 'vue'

const isDark = ref(localStorage.getItem('theme') !== 'light')

watch(isDark, (val) => {
  localStorage.setItem('theme', val ? 'dark' : 'light')
})

export function useTheme() {
  return {
    isDark,
    toggle: () => { isDark.value = !isDark.value },
  }
}
