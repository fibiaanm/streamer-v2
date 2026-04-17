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
}

export interface WorkerOutput {
  type:      'result' | 'error'
  jobId:     string
  filename:  string
  mime:      string
  buffer?:   ArrayBuffer
  message?:  string
}

import { targetDims } from './imageExportUtils'

const MIME: Record<WorkerInput['format'], string> = {
  jpeg: 'image/jpeg',
  webp: 'image/webp',
  png:  'image/png',
}

self.onmessage = async (e: MessageEvent<WorkerInput>) => {
  const input = e.data

  try {
    const bitmap = await createImageBitmap(input.blob)
    const { w, h } = targetDims(input)

    const canvas = new OffscreenCanvas(w, h)
    const ctx    = canvas.getContext('2d')!
    ctx.drawImage(bitmap, 0, 0, w, h)
    bitmap.close()

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
