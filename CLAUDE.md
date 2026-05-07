# Reglas de trabajo — streamer-v2

## Mantén la casa ordenada

Antes de cerrar cualquier tarea, verifica que el repositorio quede limpio:

- **Archivos en su lugar** — nada suelto en la raíz que debería estar en una subcarpeta
- **Sin archivos temporales** — no dejes `.bak`, `test.php`, `prueba.js` ni nada que no sea parte del proyecto
- **Documentación sincronizada** — si cambias comportamiento, actualiza el `.mdc` de la etapa correspondiente y el `status:` del frontmatter
- **Nomenclatura coherente** — sigue la estructura existente; no inventes convenciones nuevas

## Dónde va cada cosa

```
backend/         Aplicación Laravel completa (app/, config/, routes/, tests/…)
docker/          Dockerfiles e configs de cada servicio de infraestructura
make/            Archivos .mk incluidos por el Makefile raíz
services/        Código fuente de servicios independientes (socketio, compositor…)
docs/            Documentación del proyecto (no tocar sin motivo)
```

## Repo management
No commits sin aprobación previa, hacer un commit no es permiso para hacer commits de todo lo posterior

## Convenciones de código

- **`use Throwable;` siempre** — nunca `\Throwable` inline en catch blocks ni en type hints.
  Lo mismo aplica a cualquier clase del namespace global que uses en un archivo con namespace propio
  (`RuntimeException`, `Exception`, `Throwable`, `Closure`, etc.).
- **Imports siempre arriba** — nunca usar el FQCN inline (`\Illuminate\Support\Facades\DB::...`).
  Cualquier clase que se use dentro de un archivo con namespace propio debe declararse con `use` al inicio.

## Manejo de fechas y horas

- **Todo se almacena en UTC** — base de datos, Laravel (`config/app.php timezone: UTC`), jobs queue.
- **Frontend: usar siempre `useDate`** (`resources/js/composables/core/useDate.ts`). Nunca `toLocaleString()` sin `timeZone`, ni `getHours()` / `getDate()` de instancia (usan la zona del browser, no la del usuario).
  - Para Unix timestamps (segundos): `formatTimestamp(ts)`.
  - Para strings de MySQL (`2026-05-06 08:00:00`): normalizar a ISO UTC primero → `d.replace(' ', 'T') + 'Z'`.
- **LLM → tools**: los campos `event_at`, `until`, etc. se envían como ISO 8601 UTC. El system prompt pasa la hora actual en la zona del usuario para que el modelo convierta correctamente.
- Detalle completo → `docs/assistant/overview.mdc` § Zona horaria.

## Referencia rápida

- Contexto del proyecto → `docs/CONTEXT.mdc`
- Plan de etapas       → `docs/plan.mdc`
- Estándares de tests  → `docs/testing.mdc`
- Etapa actual         → `docs/stages/NN-nombre.mdc`
- Íconos SVG           → `docs/icons.mdc`
- Apps y rutas         → `docs/apps.mdc`
