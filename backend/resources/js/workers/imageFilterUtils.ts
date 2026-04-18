import type { FilterState } from '@/types/imageStudio'

export function clamp(v: number): number { return v < 0 ? 0 : v > 255 ? 255 : v }

export function applyPixelFilters(data: Uint8ClampedArray, f: FilterState): void {
  const brightness  = f.brightness  / 100
  const contrast    = f.contrast    / 100
  const saturation  = f.saturation  / 100
  const temperature = (f.temperature / 100) * 0.2
  const gamma       = Math.pow(2, f.shadows / 100)

  const c255    = contrast * 255
  const cFactor = (259 * (c255 + 255)) / (255 * (259 - c255))

  const needsBrightness  = f.brightness  !== 0
  const needsContrast    = f.contrast    !== 0
  const needsSaturation  = f.saturation  !== 0
  const needsGamma       = f.shadows     !== 0
  const needsTemperature = f.temperature !== 0

  for (let i = 0; i < data.length; i += 4) {
    let r = data[i], g = data[i + 1], b = data[i + 2]

    if (needsBrightness) {
      const add = brightness * 255
      r = clamp(r + add); g = clamp(g + add); b = clamp(b + add)
    }

    if (needsContrast) {
      r = clamp(cFactor * (r - 128) + 128)
      g = clamp(cFactor * (g - 128) + 128)
      b = clamp(cFactor * (b - 128) + 128)
    }

    if (needsSaturation) {
      const lum = 0.2126 * r + 0.7152 * g + 0.0722 * b
      r = clamp(lum + (r - lum) * (1 + saturation))
      g = clamp(lum + (g - lum) * (1 + saturation))
      b = clamp(lum + (b - lum) * (1 + saturation))
    }

    if (needsGamma) {
      r = clamp(Math.pow(r / 255, 1 / gamma) * 255)
      g = clamp(Math.pow(g / 255, 1 / gamma) * 255)
      b = clamp(Math.pow(b / 255, 1 / gamma) * 255)
    }

    if (needsTemperature) {
      r = clamp(r * (1 + temperature))
      b = clamp(b * (1 - temperature))
    }

    data[i] = r; data[i + 1] = g; data[i + 2] = b
  }
}

export function applySharpness(data: Uint8ClampedArray, w: number, h: number, amount: number): void {
  const s   = amount / 100
  const src = new Uint8ClampedArray(data)
  for (let y = 1; y < h - 1; y++) {
    for (let x = 1; x < w - 1; x++) {
      const i = (y * w + x) * 4
      for (let c = 0; c < 3; c++) {
        const center = src[i + c]
        const top    = src[((y - 1) * w + x)     * 4 + c]
        const bottom = src[((y + 1) * w + x)     * 4 + c]
        const left   = src[(y       * w + x - 1) * 4 + c]
        const right  = src[(y       * w + x + 1) * 4 + c]
        data[i + c]  = clamp((1 + 4 * s) * center - s * (top + bottom + left + right))
      }
    }
  }
}
