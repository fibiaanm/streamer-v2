type JSZipType = typeof import('jszip')['default']

let _JSZip: JSZipType | null = null

async function loadJSZip(): Promise<JSZipType> {
  if (!_JSZip) _JSZip = (await import('jszip')).default
  return _JSZip
}

const IMAGE_EXTENSIONS = new Set(['jpg', 'jpeg', 'png', 'webp', 'gif', 'heic', 'heif'])
const EXTENSION_MIME: Record<string, string> = {
  jpg: 'image/jpeg', jpeg: 'image/jpeg',
  png: 'image/png',  webp: 'image/webp', gif: 'image/gif',
  heic: 'image/heic', heif: 'image/heif',
}

export async function extractImagesFromZip(zipFile: File): Promise<File[]> {
  const JSZip = await loadJSZip()
  const zip = await JSZip.loadAsync(zipFile)

  const entries = Object.values(zip.files).filter(entry => {
    if (entry.dir) return false
    const ext = entry.name.split('.').pop()?.toLowerCase() ?? ''
    return IMAGE_EXTENSIONS.has(ext)
  })

  const files = await Promise.all(entries.map(async entry => {
    const blob = await entry.async('blob')
    const filename = entry.name.split('/').pop() ?? entry.name
    const ext = filename.split('.').pop()?.toLowerCase() ?? ''
    return new File([blob], filename, { type: EXTENSION_MIME[ext] ?? 'image/jpeg' })
  }))

  return files
}

export interface ZipEntry {
  name:   string
  buffer: ArrayBuffer
}

export async function packZip(entries: ZipEntry[]): Promise<Blob> {
  const JSZip = await loadJSZip()
  const zip   = new JSZip()
  for (const e of entries) zip.file(e.name, e.buffer)
  return zip.generateAsync({ type: 'blob', compression: 'DEFLATE', compressionOptions: { level: 6 } })
}
