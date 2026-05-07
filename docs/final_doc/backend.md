# Backend — Convenciones transversales

## Idioma del usuario

`users.lang` — código BCP 47 (`en`, `es`), default `'en'`. Determina el idioma de los mensajes que el sistema envía al usuario.

Los textos viven en `backend/lang/{lang}/` como archivos PHP estáticos. Para añadir un idioma nuevo basta crear el archivo con las mismas claves. Fallback global: `en`.
