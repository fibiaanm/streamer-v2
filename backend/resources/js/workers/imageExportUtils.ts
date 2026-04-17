export function targetDims(input: {
  naturalWidth:  number
  naturalHeight: number
  resizeMode:    'original' | 'width' | 'height'
  resizeValue?:  number
}): { w: number; h: number } {
  const { naturalWidth: nw, naturalHeight: nh, resizeMode, resizeValue } = input
  if (resizeMode === 'width'  && resizeValue) return { w: resizeValue, h: Math.round(nh * (resizeValue / nw)) }
  if (resizeMode === 'height' && resizeValue) return { h: resizeValue, w: Math.round(nw * (resizeValue / nh)) }
  return { w: nw, h: nh }
}
