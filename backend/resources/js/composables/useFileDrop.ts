import { ref } from 'vue'

export function useFileDrop(onDrop: (files: File[]) => void) {
  const isDragging = ref(false)
  let dragDepth = 0

  function onDragEnter(e: DragEvent) {
    if (!e.dataTransfer?.types.includes('Files')) return
    dragDepth++
    isDragging.value = true
  }

  function onDragLeave() {
    dragDepth--
    if (dragDepth <= 0) { dragDepth = 0; isDragging.value = false }
  }

  function onDragOver(e: DragEvent) {
    e.preventDefault()
  }

  function handleDrop(e: DragEvent) {
    e.preventDefault()
    dragDepth = 0
    isDragging.value = false
    const files = Array.from(e.dataTransfer?.files ?? [])
    if (files.length) onDrop(files)
  }

  return { isDragging, onDragEnter, onDragLeave, onDragOver, handleDrop }
}
