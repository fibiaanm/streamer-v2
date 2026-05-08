# Frontend — Convenciones transversales

## Fechas y horas

Usar siempre `useDate` (`composables/core/useDate.ts`). Nunca `toLocaleString()` sin `timeZone`.

Strings MySQL → normalizar antes: `d.replace(' ', 'T') + 'Z'`.

## Composables y tipos

- Todo TypeScript. Exports como arrow functions: `export const useX = () => ...`
- Estado compartido: `createSharedComposableById(id, factory)`
- Tipos de app → `types/index.ts`, tipos de admin → `types/admin.ts`
