export interface LocalWorkspace {
  id:         string
  name:       string
  status:     'active' | 'archived'
  path:       string       // UUIDs separados por '.': 'a' (raíz) o 'a.b.c' (anidado)
  parent_id:  string | null
  created_at: string       // ISO 8601
}
