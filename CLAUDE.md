# Reglas de trabajo — streamer-v2

## Mantén la casa ordenada

Antes de cerrar cualquier tarea, verifica que el repositorio quede limpio:

- **Archivos en su lugar** — nada suelto en la raíz que debería estar en una subcarpeta
- **Sin archivos temporales** — no dejes `.bak`, `test.php`, `prueba.js` ni nada que no sea parte del proyecto
- **Documentación sincronizada** — si cambias comportamiento, actualiza el `.mdc` de la etapa correspondiente y el `status:` del frontmatter
- **Nomenclatura coherente** — sigue la estructura existente; no inventes convenciones nuevas

## Dónde va cada cosa

```
docker/          Dockerfiles e configs de cada servicio de infraestructura
make/            Archivos .mk incluidos por el Makefile raíz
services/        Código fuente de servicios independientes (socketio, compositor…)
docs/            Documentación del proyecto (no tocar sin motivo)
app/             Código Laravel — solo existe desde etapa 02
```

## Referencia rápida

- Contexto del proyecto → `docs/CONTEXT.mdc`
- Plan de etapas       → `docs/plan.mdc`
- Estándares de tests  → `docs/testing.mdc`
- Etapa actual         → `docs/stages/NN-nombre.mdc`
