// ─── String unions ────────────────────────────────────────────────────────────

export type StudioView      = 'gallery' | 'export'
export type SessionStage    = 'upload' | 'edit' | 'export'
export type ImageItemStatus = 'idle' | 'editing' | 'exporting' | 'done' | 'error'
export type ExportFormat    = 'jpeg' | 'webp' | 'png'
export type ResizeMode      = 'original' | 'custom'

// ─── Value objects ────────────────────────────────────────────────────────────

export interface ImageSource {
  file: File
  dataUrl: string
  naturalWidth: number
  naturalHeight: number
  sizeBytes: number
}

export interface CropState {
  x: number
  y: number
  width: number
  height: number
}

export interface ResizeOptions {
  mode: ResizeMode
  width: number
  height: number
  lockAR: boolean
}

// ─── Entities ─────────────────────────────────────────────────────────────────

export interface ImageItem {
  id: string
  source: ImageSource
  status: ImageItemStatus
  crop?: CropState
}

export interface ExportConfig {
  id: string
  label: string
  format: ExportFormat
  quality: number
  resize: ResizeOptions
}
