import { createApp, h } from 'vue'
import type { DefineComponent } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { createRouter, createWebHistory } from 'vue-router'
import { createPinia } from 'pinia'
import { routes } from './router/routes'
import { authGuard } from './router/guards'

const pages = import.meta.glob<{ default: DefineComponent }>('./Pages/**/*.vue', { eager: true })

createInertiaApp({
  resolve: (name) => pages[`./Pages/${name}.vue`],
  setup({ el, App, props, plugin }) {
    const router = createRouter({
      history: createWebHistory(),
      routes,
    })

    router.beforeEach(authGuard)

    createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(router)
      .use(createPinia())
      .mount(el)
  },
})
