import type { CropState, FilterState } from '@/types/imageStudio'
import { targetDims } from './imageExportUtils'
import { applyPixelFilters, applySharpness } from './imageFilterUtils'

export interface WorkerInput {
  jobId:         string
  blob:          Blob
  filename:      string
  format:        'jpeg' | 'webp' | 'png'
  quality:       number   // 0–100
  resizeMode:    'original' | 'width' | 'height'
  resizeValue?:  number
  naturalWidth:  number
  naturalHeight: number
  rotation?:     number     // 0 | 90 | 180 | 270
  cropState?:    CropState
  filters?:      FilterState
}

export interface WorkerOutput {
  type:      'result' | 'error'
  jobId:     string
  filename:  string
  mime:      string
  buffer?:   ArrayBuffer
  message?:  string
}

const MIME: Record<WorkerInput['format'], string> = {
  jpeg: 'image/jpeg',
  webp: 'image/webp',
  png:  'image/png',
}

// ─── Worker entry ─────────────────────────────────────────────────────────────

self.onmessage = async (e: MessageEvent<WorkerInput>) => {
  const input = e.data

  try {
    const bitmap    = await createImageBitmap(input.blob)
    const { w, h }  = targetDims(input)
    const rot       = (input.rotation ?? 0) % 360

    // Source region: apply crop (or full image if none)
    const sx = input.cropState?.x      ?? 0
    const sy = input.cropState?.y      ?? 0
    const sw = input.cropState?.width  ?? input.naturalWidth
    const sh = input.cropState?.height ?? input.naturalHeight

    const canvas = new OffscreenCanvas(w, h)
    const ctx    = canvas.getContext('2d')!

    if (rot === 0) {
      ctx.drawImage(bitmap, sx, sy, sw, sh, 0, 0, w, h)
    } else {
      ctx.translate(w / 2, h / 2)
      ctx.rotate((rot * Math.PI) / 180)
      if (rot === 90 || rot === 270) {
        ctx.drawImage(bitmap, sx, sy, sw, sh, -h / 2, -w / 2, h, w)
      } else {
        ctx.drawImage(bitmap, sx, sy, sw, sh, -w / 2, -h / 2, w, h)
      }
    }

    bitmap.close()

    // Apply pixel-level filters if any are non-zero
    const f = input.filters
    if (f) {
      const hasStandard = f.brightness !== 0 || f.contrast !== 0 || f.saturation !== 0
                       || f.shadows !== 0 || f.temperature !== 0
      const hasSharpness = f.sharpness !== 0

      if (hasStandard || hasSharpness) {
        const imgData = ctx.getImageData(0, 0, w, h)
        if (hasStandard)  applyPixelFilters(imgData.data, f)
        if (hasSharpness) applySharpness(imgData.data, w, h, f.sharpness)
        ctx.putImageData(imgData, 0, 0)
      }
    }

    const mime    = MIME[input.format]
    const quality = input.format === 'png' ? undefined : input.quality / 100
    const outBlob = await canvas.convertToBlob({ type: mime, quality })
    const buffer  = await outBlob.arrayBuffer()

    const result: WorkerOutput = { type: 'result', jobId: input.jobId, filename: input.filename, mime, buffer }
    self.postMessage(result, [buffer])
  } catch (err) {
    const result: WorkerOutput = { type: 'error', jobId: input.jobId, filename: input.filename, mime: '', message: String(err) }
    self.postMessage(result)
  }
}
