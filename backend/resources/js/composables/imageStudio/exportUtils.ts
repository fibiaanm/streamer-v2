const EXT: Record<'jpeg' | 'webp' | 'png', string> = { jpeg: 'jpg', webp: 'webp', png: 'png' }

export function buildFilename(
  label: string,
  fallback: string,
  mode: string,
  value: number | undefined,
  format: 'jpeg' | 'webp' | 'png',
): string {
  const base   = label.trim() || fallback
  const suffix = mode === 'width' ? `${value ?? '?'}w` : mode === 'height' ? `${value ?? '?'}h` : 'original'
  return `${base}@${suffix}.${EXT[format]}`
}

export function deduplicateNames(
  entries: { name: string; buffer: ArrayBuffer }[],
): { name: string; buffer: ArrayBuffer }[] {
  const seen = new Map<string, number>()
  return entries.map(({ name, buffer }) => {
    const count  = seen.get(name) ?? 0
    seen.set(name, count + 1)
    const deduped = count === 0 ? name : name.replace(/(\.[^.]+)$/, `(${count + 1})$1`)
    return { name: deduped, buffer }
  })
}
