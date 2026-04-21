import { ref } from 'vue'
import type { RawImageData } from '@/types/imageStudio'
import { extractImagesFromZip } from '@/composables/useZip'
import { convertHeic } from '@/composables/imageStudio/useHeicConvert'

const ACCEPTED_MIME = new Set(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
const ZIP_MIME      = new Set(['application/zip', 'application/x-zip-compressed', 'application/x-zip'])
const HEIC_MIME     = new Set(['image/heic', 'image/heif'])

function readFile(file: File): Promise<RawImageData> {
  return new Promise((resolve, reject) => {
    const reader = new FileReader()
    reader.onerror = reject
    reader.onload = (e) => {
      const dataUrl = e.target?.result as string
      const img = new Image()
      img.onerror = reject
      img.onload = () => resolve({
        file,
        dataUrl,
        naturalWidth:  img.naturalWidth,
        naturalHeight: img.naturalHeight,
        sizeBytes:     file.size,
      })
      img.src = dataUrl
    }
    reader.readAsDataURL(file)
  })
}

async function processFile(file: File, onFile: (raw: RawImageData) => void): Promise<void> {
  const ext = file.name.split('.').pop()?.toLowerCase() ?? ''

  if (ZIP_MIME.has(file.type) || ext === 'zip') {
    const extracted = await extractImagesFromZip(file)
    await Promise.all(extracted.map(f => processFile(f, onFile)))
    return
  }

  let target = file
  if (HEIC_MIME.has(file.type) || ext === 'heic' || ext === 'heif') {
    target = await convertHeic(file)
  }

  if (!ACCEPTED_MIME.has(target.type)) return

  const raw = await readFile(target)
  onFile(raw)
}

const isProcessing = ref(false)

export function useFileProcessor() {
  async function processFiles(files: File[], onFile: (raw: RawImageData) => void): Promise<void> {
    if (!files.length) return
    isProcessing.value = true
    try {
      await Promise.all(files.map(f => processFile(f, onFile)))
    } finally {
      isProcessing.value = false
    }
  }

  return { isProcessing, processFiles }
}
