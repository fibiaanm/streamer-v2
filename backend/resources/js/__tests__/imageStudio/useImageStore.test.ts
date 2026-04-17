import { describe, it, expect, beforeEach } from 'vitest'
import { useImageStore } from '@/composables/imageStudio/useImageStore'
import type { RawImageData } from '@/types/imageStudio'

function mockRaw(name = 'photo.jpg', w = 800, h = 600): RawImageData {
  return {
    file:          new File([''], name, { type: 'image/jpeg' }),
    dataUrl:       'data:image/jpeg;base64,/9j/',
    naturalWidth:  w,
    naturalHeight: h,
    sizeBytes:     12345,
  }
}

describe('useImageStore', () => {
  const store = useImageStore()

  beforeEach(() => {
    store.items.value        = []
    store.activeItemId.value = null
  })

  // ── add ─────────────────────────────────────────────────────────────────────

  describe('add', () => {
    it('adds items to the list', () => {
      store.add([mockRaw('a.jpg'), mockRaw('b.jpg')])
      expect(store.items.value).toHaveLength(2)
    })

    it('strips the extension from the filename to build the name', () => {
      store.add([mockRaw('landscape.jpg')])
      expect(store.items.value[0].name).toBe('landscape')
    })

    it('sets activeItemId to the first item when store is empty', () => {
      store.add([mockRaw('a.jpg'), mockRaw('b.jpg')])
      expect(store.activeItemId.value).toBe(store.items.value[0].id)
    })

    it('does not change activeItemId when items already exist', () => {
      store.add([mockRaw('a.jpg')])
      const firstId = store.activeItemId.value
      store.add([mockRaw('b.jpg')])
      expect(store.activeItemId.value).toBe(firstId)
    })

    it('creates an initial export config per item', () => {
      store.add([mockRaw('photo.jpg')])
      expect(store.items.value[0].exportConfigs).toHaveLength(1)
    })

    it('initial export config uses item name as label', () => {
      store.add([mockRaw('sunset.jpg')])
      expect(store.items.value[0].exportConfigs[0].label).toBe('sunset')
    })

    it('stores naturalWidth and naturalHeight from source', () => {
      store.add([mockRaw('img.jpg', 1920, 1080)])
      const src = store.items.value[0].source
      expect(src.naturalWidth).toBe(1920)
      expect(src.naturalHeight).toBe(1080)
    })
  })

  // ── remove ───────────────────────────────────────────────────────────────────

  describe('remove', () => {
    it('removes the item by id', () => {
      store.add([mockRaw('a.jpg'), mockRaw('b.jpg')])
      const id = store.items.value[0].id
      store.remove(id)
      expect(store.items.value).toHaveLength(1)
      expect(store.items.value.find(i => i.id === id)).toBeUndefined()
    })

    it('updates activeItemId to next item when active is removed', () => {
      store.add([mockRaw('a.jpg'), mockRaw('b.jpg')])
      const firstId  = store.items.value[0].id
      const secondId = store.items.value[1].id
      store.setActive(firstId)
      store.remove(firstId)
      expect(store.activeItemId.value).toBe(secondId)
    })

    it('sets activeItemId to null when last item is removed', () => {
      store.add([mockRaw('solo.jpg')])
      store.remove(store.items.value[0].id)
      expect(store.activeItemId.value).toBeNull()
    })

    it('does nothing when id does not exist', () => {
      store.add([mockRaw('a.jpg')])
      store.remove('nonexistent')
      expect(store.items.value).toHaveLength(1)
    })
  })

  // ── setActive ────────────────────────────────────────────────────────────────

  describe('setActive', () => {
    it('updates activeItemId', () => {
      store.add([mockRaw('a.jpg'), mockRaw('b.jpg')])
      const secondId = store.items.value[1].id
      store.setActive(secondId)
      expect(store.activeItemId.value).toBe(secondId)
    })
  })

  // ── rename ───────────────────────────────────────────────────────────────────

  describe('rename', () => {
    it('updates the item name', () => {
      store.add([mockRaw('old.jpg')])
      const id = store.items.value[0].id
      store.rename(id, 'new-name')
      expect(store.items.value[0].name).toBe('new-name')
    })

    it('does nothing for unknown id', () => {
      store.add([mockRaw('photo.jpg')])
      store.rename('ghost', 'anything')
      expect(store.items.value[0].name).toBe('photo')
    })
  })

  // ── setStatus ────────────────────────────────────────────────────────────────

  describe('setStatus', () => {
    it.each(['idle', 'exporting', 'done', 'error'] as const)('sets status to %s', (status) => {
      store.add([mockRaw('a.jpg')])
      const id = store.items.value[0].id
      store.setStatus(id, status)
      expect(store.items.value[0].status).toBe(status)
    })
  })

  // ── addExportConfig ──────────────────────────────────────────────────────────

  describe('addExportConfig', () => {
    it('prepends a new config', () => {
      store.add([mockRaw('img.jpg')])
      const id = store.items.value[0].id
      store.addExportConfig(id)
      expect(store.items.value[0].exportConfigs).toHaveLength(2)
    })

    it('new config is at index 0 (prepended)', () => {
      store.add([mockRaw('img.jpg')])
      const id          = store.items.value[0].id
      const originalCfg = store.items.value[0].exportConfigs[0].id
      store.addExportConfig(id)
      expect(store.items.value[0].exportConfigs[1].id).toBe(originalCfg)
    })

    it('new config inherits item name as label', () => {
      store.add([mockRaw('banner.jpg')])
      const id = store.items.value[0].id
      store.rename(id, 'my-banner')
      store.addExportConfig(id)
      expect(store.items.value[0].exportConfigs[0].label).toBe('my-banner')
    })

    it('does nothing for unknown itemId', () => {
      store.add([mockRaw('a.jpg')])
      store.addExportConfig('ghost')
      expect(store.items.value[0].exportConfigs).toHaveLength(1)
    })
  })

  // ── removeExportConfig ───────────────────────────────────────────────────────

  describe('removeExportConfig', () => {
    it('removes the config by id', () => {
      store.add([mockRaw('img.jpg')])
      const item     = store.items.value[0]
      store.addExportConfig(item.id)
      const cfgId = item.exportConfigs[0].id
      store.removeExportConfig(item.id, cfgId)
      expect(item.exportConfigs.find(c => c.id === cfgId)).toBeUndefined()
    })
  })

  // ── updateExportConfig ───────────────────────────────────────────────────────

  describe('updateExportConfig', () => {
    it('patches format', () => {
      store.add([mockRaw('img.jpg')])
      const item  = store.items.value[0]
      const cfgId = item.exportConfigs[0].id
      store.updateExportConfig(item.id, cfgId, { format: 'png' })
      expect(item.exportConfigs[0].format).toBe('png')
    })

    it('patches quality without affecting other fields', () => {
      store.add([mockRaw('img.jpg')])
      const item  = store.items.value[0]
      const cfgId = item.exportConfigs[0].id
      const originalFormat = item.exportConfigs[0].format
      store.updateExportConfig(item.id, cfgId, { quality: 50 })
      expect(item.exportConfigs[0].quality).toBe(50)
      expect(item.exportConfigs[0].format).toBe(originalFormat)
    })

    it('does nothing for unknown itemId', () => {
      store.add([mockRaw('img.jpg')])
      const item  = store.items.value[0]
      const cfgId = item.exportConfigs[0].id
      store.updateExportConfig('ghost', cfgId, { format: 'png' })
      expect(item.exportConfigs[0].format).toBe('webp')
    })
  })

  // ── totalOutputs ─────────────────────────────────────────────────────────────

  describe('totalOutputs', () => {
    it('is 0 when store is empty', () => {
      expect(store.totalOutputs.value).toBe(0)
    })

    it('equals sum of all exportConfigs lengths', () => {
      store.add([mockRaw('a.jpg'), mockRaw('b.jpg')])
      const [a, b] = store.items.value
      store.addExportConfig(a.id)
      store.addExportConfig(b.id)
      store.addExportConfig(b.id)
      // a: 2 configs, b: 3 configs → total 5
      expect(store.totalOutputs.value).toBe(5)
    })

    it('updates reactively when config is removed', () => {
      store.add([mockRaw('img.jpg')])
      const item  = store.items.value[0]
      store.addExportConfig(item.id)
      expect(store.totalOutputs.value).toBe(2)
      store.removeExportConfig(item.id, item.exportConfigs[0].id)
      expect(store.totalOutputs.value).toBe(1)
    })
  })

  // ── activeItem ───────────────────────────────────────────────────────────────

  describe('activeItem', () => {
    it('returns null when no item is active', () => {
      expect(store.activeItem.value).toBeNull()
    })

    it('returns the active item', () => {
      store.add([mockRaw('a.jpg'), mockRaw('b.jpg')])
      const secondId = store.items.value[1].id
      store.setActive(secondId)
      expect(store.activeItem.value?.id).toBe(secondId)
    })
  })
})
