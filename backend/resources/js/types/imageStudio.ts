// ─── String unions ────────────────────────────────────────────────────────────

export type StudioView      = 'gallery' | 'export'
export type SessionStage    = 'upload' | 'edit' | 'export'
export type ImageItemStatus = 'idle' | 'editing' | 'exporting' | 'done' | 'error'
export type ExportFormat    = 'jpeg' | 'webp' | 'png'
export type ResizeMode      = 'original' | 'width' | 'height'

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

export interface FilterState {
  brightness:   number  // -100..100, default 0
  contrast:     number
  saturation:   number
  shadows:      number
  sharpness:    number
  temperature:  number
}

// ─── DTOs ─────────────────────────────────────────────────────────────────────

export interface DropPayload {
  files: File[]
}

export interface RawImageData {
  file:          File
  dataUrl:       string
  naturalWidth:  number
  naturalHeight: number
  sizeBytes:     number
}

// ─── Entities ─────────────────────────────────────────────────────────────────

export interface ExportConfig {
  id:      string
  label:   string
  format:  ExportFormat
  quality: number        // 0–100
  resize: {
    mode:   ResizeMode
    value?: number       // px — only when mode !== 'original'
  }
}

export interface ImageItem {
  id:            string
  name:          string
  source:        ImageSource
  status:        ImageItemStatus
  rotation:      number        // 0 | 90 | 180 | 270
  crop?:         CropState
  filters:       FilterState
  exportConfigs: ExportConfig[]
}
