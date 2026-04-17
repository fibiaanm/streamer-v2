import { describe, it, expect } from 'vitest'
import { targetDims } from '@/workers/imageExportUtils'

const base = { naturalWidth: 800, naturalHeight: 600 }

describe('targetDims', () => {
  describe('original mode', () => {
    it('returns natural dimensions unchanged', () => {
      expect(targetDims({ ...base, resizeMode: 'original' })).toEqual({ w: 800, h: 600 })
    })
    it('ignores resizeValue if present', () => {
      expect(targetDims({ ...base, resizeMode: 'original', resizeValue: 100 })).toEqual({ w: 800, h: 600 })
    })
  })

  describe('width mode', () => {
    it('scales height proportionally', () => {
      // 600 * (400 / 800) = 300
      expect(targetDims({ ...base, resizeMode: 'width', resizeValue: 400 })).toEqual({ w: 400, h: 300 })
    })
    it('rounds fractional height', () => {
      // naturalHeight=667, naturalWidth=1000, target width=300 → h = Math.round(667 * 0.3) = 200
      expect(targetDims({ naturalWidth: 1000, naturalHeight: 667, resizeMode: 'width', resizeValue: 300 })).toEqual({ w: 300, h: 200 })
    })
    it('falls back to natural dims when resizeValue is undefined', () => {
      expect(targetDims({ ...base, resizeMode: 'width' })).toEqual({ w: 800, h: 600 })
    })
    it('falls back to natural dims when resizeValue is 0', () => {
      expect(targetDims({ ...base, resizeMode: 'width', resizeValue: 0 })).toEqual({ w: 800, h: 600 })
    })
  })

  describe('height mode', () => {
    it('scales width proportionally', () => {
      // 800 * (300 / 600) = 400
      expect(targetDims({ ...base, resizeMode: 'height', resizeValue: 300 })).toEqual({ h: 300, w: 400 })
    })
    it('rounds fractional width', () => {
      // naturalWidth=667, naturalHeight=1000, target height=300 → w = Math.round(667 * 0.3) = 200
      expect(targetDims({ naturalWidth: 667, naturalHeight: 1000, resizeMode: 'height', resizeValue: 300 })).toEqual({ h: 300, w: 200 })
    })
    it('falls back to natural dims when resizeValue is undefined', () => {
      expect(targetDims({ ...base, resizeMode: 'height' })).toEqual({ w: 800, h: 600 })
    })
  })

  describe('upscaling', () => {
    it('allows upscaling via width', () => {
      expect(targetDims({ ...base, resizeMode: 'width', resizeValue: 1600 })).toEqual({ w: 1600, h: 1200 })
    })
    it('allows upscaling via height', () => {
      expect(targetDims({ ...base, resizeMode: 'height', resizeValue: 1200 })).toEqual({ h: 1200, w: 1600 })
    })
  })
})
