import { ref, onMounted, onUnmounted } from 'vue'

const isDragging = ref(false)
let _handler: ((files: File[]) => void) | null = null
let dragDepth = 0

export function useFileDrop() {
  function onDragEnter(e: DragEvent) {
    if (!e.dataTransfer?.types.includes('Files')) return
    if (!_handler) return
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
    if (files.length && _handler) _handler(files)
  }

  return { isDragging, onDragEnter, onDragLeave, onDragOver, handleDrop }
}

export function useDropHandler(callback: (files: File[]) => void) {
  onMounted(()   => { _handler = callback })
  onUnmounted(() => { _handler = null })
}
