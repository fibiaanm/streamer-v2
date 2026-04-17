import { describe, it, expect } from 'vitest'
import { deduplicateNames } from '@/composables/imageStudio/exportUtils'

const buf = new ArrayBuffer(0)

function names(entries: { name: string; buffer: ArrayBuffer }[]): string[] {
  return entries.map(e => e.name)
}

describe('deduplicateNames', () => {
  it('leaves unique names unchanged', () => {
    const input = [
      { name: 'a@original.webp', buffer: buf },
      { name: 'b@original.webp', buffer: buf },
      { name: 'c@original.jpg',  buffer: buf },
    ]
    expect(names(deduplicateNames(input))).toEqual(['a@original.webp', 'b@original.webp', 'c@original.jpg'])
  })

  it('adds (2) suffix before the extension on the second occurrence', () => {
    const input = [
      { name: 'hero@800w.jpg', buffer: buf },
      { name: 'hero@800w.jpg', buffer: buf },
    ]
    expect(names(deduplicateNames(input))).toEqual(['hero@800w.jpg', 'hero@800w(2).jpg'])
  })

  it('adds (2) and (3) for three equal names', () => {
    const input = [
      { name: 'photo@original.webp', buffer: buf },
      { name: 'photo@original.webp', buffer: buf },
      { name: 'photo@original.webp', buffer: buf },
    ]
    expect(names(deduplicateNames(input))).toEqual([
      'photo@original.webp',
      'photo@original(2).webp',
      'photo@original(3).webp',
    ])
  })

  it('inserts suffix immediately before the dot extension', () => {
    const input = [
      { name: 'img@200h.png', buffer: buf },
      { name: 'img@200h.png', buffer: buf },
    ]
    const result = deduplicateNames(input)
    expect(result[1].name).toBe('img@200h(2).png')
  })

  it('tracks collisions independently per name', () => {
    const input = [
      { name: 'a@original.webp', buffer: buf },
      { name: 'b@original.webp', buffer: buf },
      { name: 'a@original.webp', buffer: buf },
      { name: 'b@original.webp', buffer: buf },
    ]
    expect(names(deduplicateNames(input))).toEqual([
      'a@original.webp',
      'b@original.webp',
      'a@original(2).webp',
      'b@original(2).webp',
    ])
  })

  it('preserves buffer references', () => {
    const buf1 = new ArrayBuffer(4)
    const buf2 = new ArrayBuffer(8)
    const result = deduplicateNames([
      { name: 'img.webp', buffer: buf1 },
      { name: 'img.webp', buffer: buf2 },
    ])
    expect(result[0].buffer).toBe(buf1)
    expect(result[1].buffer).toBe(buf2)
  })

  it('returns empty array for empty input', () => {
    expect(deduplicateNames([])).toEqual([])
  })
})
