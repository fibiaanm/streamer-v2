import type { StandardTool } from '../../llm/types';

export const LIST_TOOLS: StandardTool[] = [
  {
    name: 'get_lists',
    description: 'Obtiene todas las listas del usuario (propias y compartidas aceptadas).',
    inputSchema: {
      type: 'object',
      properties: {
        include_shared: { type: 'boolean', description: 'true para incluir listas compartidas con el usuario' },
      },
    },
  },
  {
    name: 'get_list',
    description: 'Obtiene una lista con todos sus ítems.',
    inputSchema: {
      type: 'object',
      required: ['id'],
      properties: {
        id: { type: 'string' },
      },
    },
  },
  {
    name: 'create_list',
    description: 'Crea una nueva lista, opcionalmente con ítems iniciales.',
    inputSchema: {
      type: 'object',
      required: ['name'],
      properties: {
        name:  { type: 'string' },
        type:  { type: 'string' },
        items: {
          type: 'array',
          items: {
            type: 'object',
            required: ['content'],
            properties: {
              content: { type: 'string' },
            },
          },
        },
      },
    },
  },
  {
    name: 'delete_list',
    description: 'Elimina una lista. Solo el dueño puede eliminarla.',
    inputSchema: {
      type: 'object',
      required: ['id'],
      properties: {
        id: { type: 'string' },
      },
    },
  },
  {
    name: 'add_to_list',
    description: 'Añade uno o varios ítems al final de una lista.',
    inputSchema: {
      type: 'object',
      required: ['id', 'items'],
      properties: {
        id: { type: 'string' },
        items: {
          type: 'array',
          items: {
            type: 'object',
            required: ['content'],
            properties: {
              content: { type: 'string' },
            },
          },
        },
      },
    },
  },
  {
    name: 'update_list_item',
    description: 'Modifica el content, status o position de un ítem.',
    inputSchema: {
      type: 'object',
      required: ['list_id', 'item_id'],
      properties: {
        list_id:  { type: 'string' },
        item_id:  { type: 'string' },
        content:  { type: 'string' },
        status:   { type: 'string', enum: ['pending', 'done'] },
        position: { type: 'integer', minimum: 0 },
      },
    },
  },
  {
    name: 'remove_from_list',
    description: 'Elimina un ítem de una lista.',
    inputSchema: {
      type: 'object',
      required: ['list_id', 'item_id'],
      properties: {
        list_id: { type: 'string' },
        item_id: { type: 'string' },
      },
    },
  },
  {
    name: 'clear_completed_items',
    description: 'Elimina todos los ítems completados (done) de una lista. Solo el dueño puede hacerlo.',
    inputSchema: {
      type: 'object',
      required: ['id'],
      properties: {
        id: { type: 'string' },
      },
    },
  },
];
