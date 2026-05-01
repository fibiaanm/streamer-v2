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
    description: 'Crea un evento con sus disparos de recordatorio.',
    inputSchema: {
      type: 'object',
      required: ['content', 'event_at', 'type', 'reminders'],
      properties: {
        content:         { type: 'string' },
        event_at:        { type: 'string', description: 'ISO 8601 UTC' },
        event_end:       { type: 'string', description: 'ISO 8601 UTC, solo para bloques con duración' },
        type:            { type: 'string' },
        recurrence_rule: { type: 'string', description: 'iCal RRULE, ej: FREQ=WEEKLY;BYDAY=MO' },
        reminders: {
          type: 'array',
          items: {
            type: 'object',
            required: ['offset', 'message'],
            properties: {
              offset:  { type: 'string', description: 'Offset relativo, ej: "-7 days", "-1 day", "0"' },
              message: { type: 'string' },
            },
          },
        },
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
