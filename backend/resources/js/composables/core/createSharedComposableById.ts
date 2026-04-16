import { effectScope, onScopeDispose } from 'vue'
import type { EffectScope } from 'vue'

interface RegistryEntry<T> {
  instance: T
  scope: EffectScope
  count: number
}

const _registry = new Map<string, RegistryEntry<unknown>>()

function createSharedComposableById<T>(id: string, factory: () => T): T {
  if (!_registry.has(id)) {
    const scope = effectScope(true)
    const instance = scope.run(() => factory()) as T
    _registry.set(id, { instance, scope, count: 0 })
  }

  const entry = _registry.get(id) as RegistryEntry<T>
  entry.count++

  onScopeDispose(() => {
    entry.count--
    if (entry.count === 0) {
      entry.scope.stop()
      _registry.delete(id)
    }
  }, true)

  return entry.instance
}

createSharedComposableById.dispose = (id: string) => {
  const entry = _registry.get(id)
  if (entry) {
    entry.scope.stop()
    _registry.delete(id)
  }
}

export { createSharedComposableById }
