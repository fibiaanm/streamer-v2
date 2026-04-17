type JSZipType = typeof import('jszip')['default']

let _JSZip: JSZipType | null = null

async function loadJSZip(): Promise<JSZipType> {
  if (!_JSZip) _JSZip = (await import('jszip')).default
  return _JSZip
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
