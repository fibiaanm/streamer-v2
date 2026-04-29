const DB_NAME    = 'streamer_local'
const DB_VERSION = 1

let _db: Promise<IDBDatabase> | null = null

function openDb(): Promise<IDBDatabase> {
  if (_db) return _db

  _db = new Promise((resolve, reject) => {
    const req = indexedDB.open(DB_NAME, DB_VERSION)

    req.onupgradeneeded = (e) => {
      const db = (e.target as IDBOpenDBRequest).result
      if (!db.objectStoreNames.contains('workspaces')) {
        db.createObjectStore('workspaces', { keyPath: 'id' })
      }
    }

    req.onsuccess = () => resolve(req.result)
    req.onerror   = () => reject(req.error)
  })

  return _db
}

function run<T>(fn: (db: IDBDatabase) => IDBRequest<T>): Promise<T> {
  return openDb().then(
    db => new Promise((resolve, reject) => {
      const req      = fn(db)
      req.onsuccess  = () => resolve(req.result)
      req.onerror    = () => reject(req.error)
    }),
  )
}

export function idbGet<T>(store: string, key: string): Promise<T | undefined> {
  return run(db => db.transaction(store).objectStore(store).get(key))
}

export function idbPut<T>(store: string, item: T): Promise<void> {
  return run(db => db.transaction(store, 'readwrite').objectStore(store).put(item)).then(() => {})
}

export function idbDelete(store: string, key: string): Promise<void> {
  return run(db => db.transaction(store, 'readwrite').objectStore(store).delete(key)).then(() => {})
}

export function idbGetAll<T>(store: string): Promise<T[]> {
  return run(db => db.transaction(store).objectStore(store).getAll())
}
