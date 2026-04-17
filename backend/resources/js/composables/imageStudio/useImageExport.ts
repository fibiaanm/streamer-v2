import { ref, reactive } from 'vue'
import type { WorkerInput, WorkerOutput } from '@/workers/imageExport.worker'
import ImageExportWorker from '@/workers/imageExport.worker.ts?worker'
import { packZip } from '@/composables/useZip'
import { useImageStore } from './useImageStore'

// ─── Helpers ──────────────────────────────────────────────────────────────────

const EXT: Record<'jpeg' | 'webp' | 'png', string> = { jpeg: 'jpg', webp: 'webp', png: 'png' }

function buildFilename(label: string, fallback: string, mode: string, value: number | undefined, format: 'jpeg' | 'webp' | 'png'): string {
  const base   = label.trim() || fallback
  const suffix = mode === 'width' ? `${value ?? '?'}w` : mode === 'height' ? `${value ?? '?'}h` : 'original'
  return `${base}@${suffix}.${EXT[format]}`
}

// ─── Pool size — increase to parallelize ─────────────────────────────────────

const POOL_SIZE = 1

// ─── Composable ───────────────────────────────────────────────────────────────

export function useImageExport() {
  const store       = useImageStore()
  const isExporting = ref(false)
  const progress    = reactive({ done: 0, total: 0 })

  async function exportAll(zipName: string): Promise<void> {
    if (isExporting.value) return

    // ── Snapshot ───────────────────────────────────────────────────────────────
    type Job = WorkerInput & { itemId: string }
    const jobs: Job[] = []

    for (const item of store.items.value) {
      if (!item.source.dataUrl || !item.exportConfigs.length) continue
      const srcBlob = await fetch(item.source.dataUrl).then(r => r.blob())

      for (const cfg of item.exportConfigs) {
        jobs.push({
          jobId:         `${item.id}::${cfg.id}`,
          itemId:        item.id,
          blob:          srcBlob,
          filename:      buildFilename(cfg.label, item.name, cfg.resize.mode, cfg.resize.value, cfg.format),
          format:        cfg.format,
          quality:       cfg.quality,
          resizeMode:    cfg.resize.mode,
          resizeValue:   cfg.resize.value,
          naturalWidth:  item.source.naturalWidth,
          naturalHeight: item.source.naturalHeight,
        })
      }
    }

    if (!jobs.length) return

    isExporting.value = true
    progress.done     = 0
    progress.total    = jobs.length

    for (const item of store.items.value) {
      if (item.exportConfigs.length) store.setStatus(item.id, 'exporting')
    }

    // ── Worker pool ────────────────────────────────────────────────────────────
    const results = new Map<string, { filename: string; buffer: ArrayBuffer }>()
    const errors  = new Set<string>()
    const queue   = [...jobs]

    await new Promise<void>((resolve) => {
      const workers = Array.from({ length: Math.min(POOL_SIZE, jobs.length) }, () => new ImageExportWorker())
      let active = 0

      function dispatch(worker: Worker) {
        const job = queue.shift()
        if (!job) return
        active++
        worker.postMessage(job)
        worker.onmessage = (e: MessageEvent<WorkerOutput>) => {
          const msg = e.data
          active--
          progress.done++

          if (msg.type === 'result' && msg.buffer) {
            results.set(msg.jobId, { filename: msg.filename, buffer: msg.buffer })
          } else {
            errors.add(job.itemId)
          }

          if (queue.length) {
            dispatch(worker)
          } else if (active === 0) {
            workers.forEach(w => w.terminate())
            resolve()
          }
        }
      }

      workers.forEach(dispatch)
    })

    // ── Update statuses ────────────────────────────────────────────────────────
    const touched = new Set(jobs.map(j => j.itemId))
    for (const id of touched) store.setStatus(id, errors.has(id) ? 'error' : 'done')

    // ── Pack + download ────────────────────────────────────────────────────────
    if (results.size) {
      const blob = await packZip(Array.from(results.values()))
      const url  = URL.createObjectURL(blob)
      Object.assign(document.createElement('a'), { href: url, download: `${zipName || 'mis-imagenes'}.zip` }).click()
      URL.revokeObjectURL(url)
    }

    isExporting.value = false
  }

  return { exportAll, isExporting, progress }
}
