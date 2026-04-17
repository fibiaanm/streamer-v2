import { describe, it, expect } from 'vitest'
import { buildFilename } from '@/composables/imageStudio/exportUtils'

describe('buildFilename', () => {
  describe('extension mapping', () => {
    it('maps jpeg → .jpg', () => {
      expect(buildFilename('img', 'fb', 'original', undefined, 'jpeg')).toBe('img@original.jpg')
    })
    it('maps webp → .webp', () => {
      expect(buildFilename('img', 'fb', 'original', undefined, 'webp')).toBe('img@original.webp')
    })
    it('maps png → .png', () => {
      expect(buildFilename('img', 'fb', 'original', undefined, 'png')).toBe('img@original.png')
    })
  })

  describe('label / fallback', () => {
    it('uses label when provided', () => {
      expect(buildFilename('hero', 'fallback', 'original', undefined, 'webp')).toBe('hero@original.webp')
    })
    it('uses fallback when label is empty string', () => {
      expect(buildFilename('', 'my-image', 'original', undefined, 'webp')).toBe('my-image@original.webp')
    })
    it('uses fallback when label is only whitespace', () => {
      expect(buildFilename('   ', 'my-image', 'original', undefined, 'webp')).toBe('my-image@original.webp')
    })
    it('trims whitespace from label before using it', () => {
      expect(buildFilename('  hero  ', 'fb', 'original', undefined, 'webp')).toBe('hero@original.webp')
    })
  })

  describe('resize suffix', () => {
    it('width mode appends <value>w', () => {
      expect(buildFilename('img', 'fb', 'width', 800, 'jpeg')).toBe('img@800w.jpg')
    })
    it('height mode appends <value>h', () => {
      expect(buildFilename('img', 'fb', 'height', 600, 'png')).toBe('img@600h.png')
    })
    it('original mode appends "original"', () => {
      expect(buildFilename('img', 'fb', 'original', undefined, 'webp')).toBe('img@original.webp')
    })
    it('width mode with undefined value uses "?"', () => {
      expect(buildFilename('img', 'fb', 'width', undefined, 'webp')).toBe('img@?w.webp')
    })
    it('height mode with undefined value uses "?"', () => {
      expect(buildFilename('img', 'fb', 'height', undefined, 'webp')).toBe('img@?h.webp')
    })
  })
})
