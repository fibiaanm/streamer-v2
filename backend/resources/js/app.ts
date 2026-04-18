import { createApp, h, effectScope } from 'vue'
import type { DefineComponent } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { createRouter, createWebHistory } from 'vue-router'
import { createPinia } from 'pinia'
import { routes } from './router/routes'
import { usePermissions } from '@/composables/core/usePermissions'

const pages = import.meta.glob<{ default: DefineComponent }>('./Pages/**/*.vue', { eager: true })

createInertiaApp({
  resolve: (name) => pages[`./Pages/${name}.vue`],
  setup({ el, App, props, plugin }) {
    const router = createRouter({
      history: createWebHistory(),
      routes,
    })

    effectScope(true).run(() => {
      const { isGuest } = usePermissions()

      router.beforeEach((to) => {
        if (to.meta.requiresAuth && isGuest.value) {
          return { path: '/login', query: { next: to.fullPath } }
        }
      })
    })

    createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(router)
      .use(createPinia())
      .mount(el)
  },
})
