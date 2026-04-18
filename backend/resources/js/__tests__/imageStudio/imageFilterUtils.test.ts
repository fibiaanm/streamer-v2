import { describe, it, expect } from 'vitest'
import { clamp, applyPixelFilters, applySharpness } from '@/workers/imageFilterUtils'
import type { FilterState } from '@/types/imageStudio'

const zero: FilterState = { brightness: 0, contrast: 0, saturation: 0, shadows: 0, sharpness: 0, temperature: 0 }

function pixel(r: number, g: number, b: number, a = 255): Uint8ClampedArray {
  return new Uint8ClampedArray([r, g, b, a])
}

function rgba(data: Uint8ClampedArray): [number, number, number, number] {
  return [data[0], data[1], data[2], data[3]]
}

describe('clamp', () => {
  it('clamps below 0 to 0', () => expect(clamp(-1)).toBe(0))
  it('clamps above 255 to 255', () => expect(clamp(256)).toBe(255))
  it('passes through values in range', () => expect(clamp(128)).toBe(128))
})

describe('applyPixelFilters', () => {
  describe('no-op when all filters are zero', () => {
    it('leaves mid-grey unchanged', () => {
      const d = pixel(128, 128, 128)
      applyPixelFilters(d, zero)
      expect(rgba(d)).toEqual([128, 128, 128, 255])
    })
  })

  describe('brightness', () => {
    it('+100 brightens all channels by 255', () => {
      const d = pixel(0, 0, 0)
      applyPixelFilters(d, { ...zero, brightness: 100 })
      expect(rgba(d)).toEqual([255, 255, 255, 255])
    })
    it('-100 darkens all channels to 0', () => {
      const d = pixel(255, 255, 255)
      applyPixelFilters(d, { ...zero, brightness: -100 })
      expect(rgba(d)).toEqual([0, 0, 0, 255])
    })
    it('partial brightness', () => {
      const d = pixel(100, 100, 100)
      applyPixelFilters(d, { ...zero, brightness: 50 })
      // +50% * 255 = +127.5 → clamp(227.5) = 227
      expect(d[0]).toBeCloseTo(228, 0)
    })
  })

  describe('saturation', () => {
    it('-100 desaturates to greyscale', () => {
      const d = pixel(200, 100, 50)
      applyPixelFilters(d, { ...zero, saturation: -100 })
      // Luminance: 0.2126*200 + 0.7152*100 + 0.0722*50 = 42.52 + 71.52 + 3.61 = 117.65 ≈ 118
      expect(d[0]).toBeCloseTo(118, 0)
      expect(d[1]).toBeCloseTo(118, 0)
      expect(d[2]).toBeCloseTo(118, 0)
    })
    it('does not modify grey pixels regardless of saturation', () => {
      const d = pixel(128, 128, 128)
      applyPixelFilters(d, { ...zero, saturation: 100 })
      expect(rgba(d)).toEqual([128, 128, 128, 255])
    })
  })

  describe('shadows (gamma)', () => {
    it('+100 lifts shadows (mid-grey gets brighter)', () => {
      const d = pixel(128, 128, 128)
      applyPixelFilters(d, { ...zero, shadows: 100 })
      // gamma = 2^1 = 2, result = pow(128/255, 1/2) * 255 ≈ 181
      expect(d[0]).toBeGreaterThan(128)
    })
    it('-100 crushes shadows (mid-grey gets darker)', () => {
      const d = pixel(128, 128, 128)
      applyPixelFilters(d, { ...zero, shadows: -100 })
      expect(d[0]).toBeLessThan(128)
    })
    it('black stays black regardless of gamma', () => {
      const d = pixel(0, 0, 0)
      applyPixelFilters(d, { ...zero, shadows: 100 })
      expect(d[0]).toBe(0)
    })
  })

  describe('temperature', () => {
    it('+100 warms (increases red, decreases blue)', () => {
      const d = pixel(128, 128, 128)
      applyPixelFilters(d, { ...zero, temperature: 100 })
      expect(d[0]).toBeGreaterThan(128) // red up
      expect(d[1]).toBe(128)            // green unchanged
      expect(d[2]).toBeLessThan(128)    // blue down
    })
    it('-100 cools (decreases red, increases blue)', () => {
      const d = pixel(128, 128, 128)
      applyPixelFilters(d, { ...zero, temperature: -100 })
      expect(d[0]).toBeLessThan(128)
      expect(d[2]).toBeGreaterThan(128)
    })
  })

  describe('alpha channel is never modified', () => {
    it('preserves alpha = 128 through brightness', () => {
      const d = pixel(100, 100, 100, 128)
      applyPixelFilters(d, { ...zero, brightness: 50 })
      expect(d[3]).toBe(128)
    })
  })
})

describe('applySharpness', () => {
  it('does not modify edge pixels (border is left as-is)', () => {
    // 3×3 uniform grey image — interior pixel only
    const w = 3, h = 3
    const d = new Uint8ClampedArray(w * h * 4).fill(128)
    // Set alpha to 255
    for (let i = 3; i < d.length; i += 4) d[i] = 255
    applySharpness(d, w, h, 50)
    // Center pixel of a uniform image: kernel result = (1+4s)*128 - 4s*128 = 128
    const centerR = d[(1 * w + 1) * 4]
    expect(centerR).toBeCloseTo(128, 0)
  })

  it('sharpens edge between light and dark', () => {
    // 3×3: left column = 0, right column = 255, center column = 128
    const w = 3, h = 3
    const d = new Uint8ClampedArray(w * h * 4)
    for (let y = 0; y < h; y++) {
      for (let x = 0; x < w; x++) {
        const i = (y * w + x) * 4
        d[i] = d[i + 1] = d[i + 2] = x === 0 ? 0 : x === 2 ? 255 : 128
        d[i + 3] = 255
      }
    }
    applySharpness(d, w, h, 50)
    // Center pixel (1,1): neighbors top/bottom are 128, left=0, right=255
    // result = (1+4*0.5)*128 - 0.5*(128+128+0+255) = 3*128 - 0.5*511 = 384 - 255.5 = 128.5 ≈ 129
    const centerR = d[(1 * w + 1) * 4]
    expect(centerR).toBeCloseTo(129, 0)
  })
})
