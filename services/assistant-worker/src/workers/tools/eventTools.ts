import type { StandardTool } from '../../llm/types';

export const EVENT_TOOLS: StandardTool[] = [
  {
    name: 'get_events',
    description:
      'Lista eventos del usuario. Devuelve eventos reales y ocurrencias virtuales de series recurrentes. ' +
      'Los IDs de ocurrencias virtuales tienen formato "v_{series_id}_{YYYY-MM-DD}".',
    inputSchema: {
      type: 'object',
      properties: {
        from:   { type: 'string', description: 'ISO 8601 date (default: today)' },
        to:     { type: 'string', description: 'ISO 8601 date (default: from + 30 days)' },
        type:   { type: 'string' },
        status: { type: 'string', enum: ['active', 'cancelled', 'completed'] },
      },
    },
  },
  {
    name: 'create_event',
    description:
      'Crea un evento. Los recordatorios se calculan automáticamente por el sistema según la distancia al evento. ' +
      'No es necesario ni posible especificarlos.',
    inputSchema: {
      type: 'object',
      required: ['content', 'event_at', 'type'],
      properties: {
        content:         { type: 'string' },
        event_at:        { type: 'string', description: 'ISO 8601 UTC' },
        event_end:       { type: 'string', description: 'ISO 8601 UTC, solo para bloques con duración' },
        type:            { type: 'string' },
        recurrence_rule: { type: 'string', description: 'iCal RRULE, ej: FREQ=WEEKLY;BYDAY=MO' },
        referenceable: {
          type: 'object',
          required: ['type', 'id'],
          properties: {
            type: { type: 'string', enum: ['list', 'expense'] },
            id:   { type: 'string' },
          },
        },
      },
    },
  },
  {
    name: 'update_event',
    description: 'Modifica una ocurrencia. Acepta IDs reales o virtuales ("v_...").',
    inputSchema: {
      type: 'object',
      required: ['id'],
      properties: {
        id:        { type: 'string' },
        content:   { type: 'string' },
        event_at:  { type: 'string', description: 'ISO 8601 UTC' },
        event_end: { type: 'string' },
        type:      { type: 'string' },
      },
    },
  },
  {
    name: 'cancel_event',
    description: 'Cancela una ocurrencia o toda la serie. Acepta IDs reales o virtuales ("v_...").',
    inputSchema: {
      type: 'object',
      required: ['id'],
      properties: {
        id:     { type: 'string' },
        series: { type: 'boolean', description: 'true = cancela toda la serie desde ahora', default: false },
      },
    },
  },
  {
    name: 'snooze_event',
    description:
      'Pospone una ocurrencia hasta la siguiente que caiga después de `until`. ' +
      'Acepta IDs reales o virtuales.',
    inputSchema: {
      type: 'object',
      required: ['id', 'until'],
      properties: {
        id:    { type: 'string' },
        until: { type: 'string', description: 'ISO 8601 UTC' },
      },
    },
  },
  {
    name: 'update_series',
    description:
      'Actualiza el contenido, tipo o duración de TODOS los eventos futuros de una serie recurrente. ' +
      'Acepta el ID del master, de cualquier ocurrencia real o de una ocurrencia virtual ("v_...").',
    inputSchema: {
      type: 'object',
      required: ['id'],
      properties: {
        id:        { type: 'string' },
        content:   { type: 'string' },
        type:      { type: 'string' },
        event_end: { type: 'string', description: 'ISO 8601 UTC' },
        time:      { type: 'string', description: 'New time for all occurrences in HH:MM format (user timezone)' },
      },
    },
  },
  {
    name: 'detach_event_reference',
    description: 'Desasocia el evento de su lista/gasto vinculado sin cancelarlo.',
    inputSchema: {
      type: 'object',
      required: ['event_id'],
      properties: {
        event_id: { type: 'string' },
      },
    },
  },
];
