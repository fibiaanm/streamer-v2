# Flujo de Eventos

## Visión general

El usuario describe un evento en lenguaje natural. El LLM interpreta la intención, llama la tool correspondiente, y Laravel persiste el evento y agenda los recordatorios automáticamente según la distancia al evento. El usuario no define cuántos recordatorios ni cuándo — el sistema lo calcula.

---

## Tools disponibles (LLM → backend)

| Tool | Descripción |
|---|---|
| `get_events(from?, to?, type?, status?)` | Lista eventos en un rango de fechas |
| `create_event(content, event_at, type, event_end?, referenceable?)` | Crea un evento único |
| `update_event(id, content?, event_at?, event_end?, type?)` | Modifica un evento existente |
| `cancel_event(id)` | Cancela un evento |
| `snooze_event(id, until)` | Pospone el evento hasta la fecha indicada |
| `detach_event_reference(event_id)` | Desasocia el evento de una lista o gasto vinculado |

`event_at` y `until` viajan siempre como ISO 8601 UTC. El LLM convierte desde la zona del usuario usando `current_datetime` y `effective_timezone` del system prompt.

---

## Modelo de datos

### `assistant_events`
El evento en sí. Campos clave: `content`, `event_at`, `event_end`, `type`, `status`, `user_id`, `referenceable_type`, `referenceable_id`.

### `event_reminders`
Recordatorios individuales vinculados a un evento. Campos clave: `event_id`, `kind` (digest | ahead | inline), `fire_at`, `reminder_run_id`, `status`, `fired_at`.

`reminder_run_id` es el indicador de estado activo: **non-null = pendiente**, **null = liberado** (disparado o cancelado). No se filtra por `status` para saber si está activo.

### `reminder_runs`
Agrupa los recordatorios de un mismo usuario que se disparan al mismo momento. Un solo job maneja el batch completo. Campos clave: `user_id`, `run_at`, `kind`, `job_id`, `status`.

### `type_catalogs`
Catálogo de tipos de evento. Hay tipos globales (`user_id = null`) y tipos personalizados por usuario. Si el LLM usa un tipo que no existe, el sistema lo crea automáticamente.

---

## Tipos de evento

| Caso | Descripción |
|---|---|
| Evento único | Sin serie, sin ocurrencia. El más común. |
| Ocurrencia virtual | Generada al vuelo por `GetEventsController` expandiendo RRULE. ID: `v_{series_id}_{YYYY-MM-DD}`. No tiene fila en BD hasta que el LLM actúa sobre ella. |

Los eventos virtuales los acepta el backend en `update_event`, `cancel_event` y `snooze_event`. Los distingue por el prefijo `v_` y los materializa al recibir la acción (`EventResolver::resolve`).

---

## Flujo de creación

**LLM:** llama `create_event(content, event_at, type, ...)`

**Backend:**
1. `CreateEventController::__invoke` — valida, crea el `AssistantEvent`, resuelve o crea el `TypeCatalog`
2. `ReminderScheduler::scheduleForEvent($event, $user->timezone)` — calcula la matriz de recordatorios
3. Por cada momento calculado: `ReminderRun::firstOrCreate(user_id, run_at, kind)` + crea `EventReminder`
4. Si el `ReminderRun` es nuevo: `FireReminderRun::dispatch()->delay($run->run_at)`
5. Devuelve `AssistantEventResource` al LLM

Si el evento tiene `referenceable` (lista o gasto), se valida con `MorphTypeMap` y se vincula al evento.

---

## Matriz de recordatorios

`ReminderScheduler::fromMatrix($eventAt, $timezone)` calcula los momentos de disparo según la distancia al evento, en la zona horaria del usuario:

| Distancia | Recordatorios generados |
|---|---|
| Mismo día | Inline: -1 hora y -10 minutos antes del evento |
| ≤ 2 días | Solo digest @ 6am del día del evento |
| > 2 días | Digest @ 6am + ahead @ 10am del día anterior |
| ≥ 30 días | Digest @ 6am + ahead @ 10am: -1 semana y -1 día |
| ≥ 365 días | Digest @ 6am + ahead @ 10am: -1 mes, -1 semana y -1 día |

- **digest**: lista todos los eventos del día del usuario
- **ahead**: aviso anticipado de un evento próximo
- **inline**: aviso inmediato justo antes del evento

---

## Flujo de disparo

`FireReminderRun::handle($runId)`:

1. Carga el `ReminderRun` con sus `EventReminder` y eventos asociados
2. Si no hay reminders activos → elimina el run y abort
3. Encuentra la sesión activa del usuario en su conversación
4. Construye el mensaje según `kind`: digest (lista del día), ahead (aviso de evento próximo), inline (aviso inmediato). Los textos son traducciones estáticas (`lang/*/reminders.php`), no LLM
5. `AssistantMessage::create(role: system)` en la sesión
6. Por cada `EventReminder` del run: `reminder_run_id = null`, `status = fired`, `fired_at = now()`
7. `run->update(status: fired)`
8. `MessageReceived::broadcast(...)` — el frontend recibe el mensaje por WebSocket

`SweepPendingReminders` corre cada 15 minutos como safety net: busca `ReminderRun` con `run_at <= now()` y `status = pending` que no se hayan despachado, y lanza `FireReminderRun`.

---

## Flujo de modificación

**LLM:** llama `update_event(id, event_at?, ...)`

**Backend (`UpdateEventController`):**
1. `EventResolver::resolve($id, $userId)` — si el ID es virtual, materializa la ocurrencia como excepción
2. Actualiza los campos del evento
3. Si cambia `event_at`:
   - `ReminderScheduler::releaseByEventIds([$event->id])` — libera reminders existentes
   - `ReminderScheduler::scheduleForEvent($event, $user->timezone)` — reagenda desde la nueva fecha

---

## Flujo de cancelación

**LLM:** llama `cancel_event(id)`

**Backend (`CancelEventController`):**
1. `EventResolver::resolve($id, $userId)`
2. `event->update(status: cancelled)`
3. `ReminderScheduler::releaseByEventIds([$event->id])`

`releaseByEventIds` internamente:
- Pone `reminder_run_id = null` + `status = cancelled` en los `EventReminder` activos
- Por cada `ReminderRun` afectado: `ReminderRun::cancelIfEmpty()` — si no quedan reminders activos, cancela el job en `jobs` y borra el run

---

## Flujo de snooze

**LLM:** llama `snooze_event(id, until)`

**Backend (`SnoozeEventController`):**
1. `EventResolver::resolve($id, $userId)`
2. Actualiza `event_at = until`
3. `ReminderScheduler::releaseByEventIds([$event->id])` + `scheduleForEvent($event, $user->timezone)`

---

## Archivos del sistema

### Controladores
`app/Domain/Assistant/Http/Controllers/Events/`
- `GetEventsController` — lista eventos, expande virtuales
- `CreateEventController` — crea evento + agenda recordatorios
- `UpdateEventController` — modifica + reactiva recordatorios si cambia `event_at`
- `CancelEventController` — cancela + libera recordatorios
- `SnoozeEventController` — pospone + reactiva recordatorios
- `DetachEventReferenceController` — desasocia referenceable

### Modelos
`app/Domain/Assistant/Models/`
- `AssistantEvent` — evento principal
- `EventReminder` — recordatorio individual
- `ReminderRun` — batch de disparo + `cancelIfEmpty()`

### Support
`app/Domain/Assistant/Support/`
- `ReminderScheduler` — matriz de cálculo, creación de runs, liberación
- `EventResolver` — resuelve IDs reales y virtuales

### Jobs
`app/Domain/Assistant/Jobs/`
- `FireReminderRun` — ejecuta un batch de recordatorios
- `SweepPendingReminders` — safety net cada 15 min

### Rutas
`routes/assistant.php` — rutas autenticadas de eventos (`/api/v1/assistant/events/...`)

### Worker (Node)
`services/assistant-worker/src/workers/tools/eventTools.ts` — definición de tools enviadas al LLM
`services/assistant-worker/src/prompts/reminders/` — instrucciones del LLM para crear eventos y aplicar la matriz

---

## Índices y auditoría de consultas

### Índices por tabla

**`assistant_events`**

| Índice | Columnas | Cubre |
|---|---|---|
| PK | `id` | lookup por ID |
| compuesto | `(user_id, event_at)` | rango de fechas por usuario |
| compuesto | `(user_id, status)` | filtro de estado por usuario |
| FK | `series_id` | join hacia master |
| `idx_events_masters` (parcial) | `(user_id, event_at, series_ends_at)` WHERE `series_id IS NULL AND recurrence_rule IS NOT NULL` | expansión de series |

**`event_reminders`**

| Índice | Columnas | Cubre |
|---|---|---|
| PK | `id` | lookup por ID |
| FK (implícito) | `event_id` | liberar reminders de un evento |
| simple | `reminder_run_id` | cargar reminders de un run; comprobar si el run está vacío |

**`reminder_runs`**

| Índice | Columnas | Cubre |
|---|---|---|
| PK | `id` | lookup por ID |
| FK (implícito) | `user_id` | — |
| compuesto | `(user_id, status, run_at)` | `firstOrCreate` por usuario + momento + estado |
| compuesto | `(status, run_at)` | sweep global de runs pendientes |

### Auditoría de consultas de lectura

Todas las consultas de lectura del sistema filtran por al menos una columna indexada. Auditado en 2026-05-06.

| Operación | Tabla | Filtros de la consulta | Índice utilizado |
|---|---|---|---|
| `get_events` — eventos únicos | `assistant_events` | `user_id` + `event_at BETWEEN` | `(user_id, event_at)` |
| `get_events` — masters recurrentes | `assistant_events` | `user_id` + `event_at <=` + `series_ends_at >=` | `idx_events_masters` (parcial) |
| `get_events` — ocurrencias materializadas | `assistant_events` | `user_id` + `event_at BETWEEN` | `(user_id, event_at)` |
| `EventResolver` — evento real | `assistant_events` | `id` | PK |
| `EventResolver` — master de evento virtual | `assistant_events` | `id` | PK |
| `releaseByEventIds` — pluck run IDs | `event_reminders` | `event_id IN (?)` | FK `event_id` |
| `cancelIfEmpty` — ¿quedan reminders? | `event_reminders` | `reminder_run_id` | `reminder_run_id` |
| `ReminderRun::firstOrCreate` | `reminder_runs` | `user_id` + `run_at` + `kind` + `status` | `(user_id, status, run_at)` |
| `FireReminderRun` — cargar reminders activos | `event_reminders` | `reminder_run_id` | `reminder_run_id` |
| `FireReminderRun` — buscar conversación | `assistant_conversations` | `user_id` | UNIQUE `user_id` |
| `FireReminderRun` — sesión más reciente | `assistant_sessions` | `conversation_id` ORDER BY `last_message_at` | FK `conversation_id` |
| `SweepPendingReminders` | `reminder_runs` | `status` + `run_at <=` | `(status, run_at)` |
