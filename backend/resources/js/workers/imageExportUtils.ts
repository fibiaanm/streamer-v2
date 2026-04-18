import type { CropState } from '@/types/imageStudio'

export function targetDims(input: {
  naturalWidth:  number
  naturalHeight: number
  resizeMode:    'original' | 'width' | 'height'
  resizeValue?:  number
  rotation?:     number    // 0 | 90 | 180 | 270
  cropState?:    CropState
}): { w: number; h: number } {
  const { resizeMode, resizeValue, rotation, cropState } = input

  // Base: use crop dimensions if present, otherwise full image
  const bw = cropState?.width  ?? input.naturalWidth
  const bh = cropState?.height ?? input.naturalHeight

  // 90° / 270° swaps width and height in the output
  const rot = (rotation ?? 0) % 360
  const [baseW, baseH] = (rot === 90 || rot === 270) ? [bh, bw] : [bw, bh]

  if (resizeMode === 'width'  && resizeValue) return { w: resizeValue, h: Math.round(baseH * (resizeValue / baseW)) }
  if (resizeMode === 'height' && resizeValue) return { h: resizeValue, w: Math.round(baseW * (resizeValue / baseH)) }
  return { w: baseW, h: baseH }
}
