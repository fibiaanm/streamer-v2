type Heic2AnyFn = typeof import('heic2any')['default']

let _heic2any: Heic2AnyFn | null = null

async function loadHeic2any(): Promise<Heic2AnyFn> {
  if (!_heic2any) _heic2any = (await import('heic2any')).default
  return _heic2any
}

export async function convertHeic(file: File): Promise<File> {
  const heic2any = await loadHeic2any()
  const result   = await heic2any({ blob: file, toType: 'image/jpeg', quality: 0.92 })
  const blob     = Array.isArray(result) ? result[0] : result
  const jpgName  = file.name.replace(/\.heic?$/i, '.jpg')
  return new File([blob], jpgName, { type: 'image/jpeg' })
}
