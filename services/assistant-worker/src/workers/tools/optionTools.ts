import type { StandardTool } from '../../llm/types';

export const OPTION_TOOLS: StandardTool[] = [
  {
    name: 'send_options',
    description:
      'Muestra al usuario opciones clickables en el chat. ' +
      'Úsalo cuando necesitas que el usuario elija entre alternativas concretas antes de continuar. ' +
      'La UI las renderiza como botones o un datetime picker. ' +
      'Siempre termina el turno después de llamar esta tool — no hagas más acciones en el mismo turno.',
    inputSchema: {
      type: 'object',
      required: ['content', 'options'],
      properties: {
        content: {
          type: 'string',
          description: 'Pregunta breve o contexto previo a las opciones.',
        },
        options: {
          type: 'array',
          minItems: 1,
          maxItems: 4,
          items: {
            type: 'object',
            required: ['type', 'label', 'value'],
            properties: {
              type: {
                type: 'string',
                enum: ['button', 'datetime'],
                description: '"button" para opción simple; "datetime" para un selector de fecha y hora.',
              },
              label: { type: 'string', description: 'Texto visible en el botón o picker.' },
              value: { type: 'string', description: 'Valor que se enviará cuando el usuario elija.' },
              default: {
                type: 'string',
                description: 'Valor ISO 8601 pre-cargado en el picker. Solo para type=datetime.',
              },
            },
          },
        },
      },
    },
  },
];
