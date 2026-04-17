import type { RawImageData } from '@/types/imageStudio'

const ACCEPTED_MIME = new Set(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])

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

export async function readImageFiles(files: File[]): Promise<RawImageData[]> {
  const valid = files.filter(f => ACCEPTED_MIME.has(f.type))
  return Promise.all(valid.map(readFile))
}
