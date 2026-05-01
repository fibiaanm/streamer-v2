import type { LaravelClient } from '../../api/LaravelClient';
import type { ToolCall } from '../../llm/types';
import { log } from '../../logger';

export class ToolExecutor {
  private readonly base: string;

  constructor(
    private readonly laravel: LaravelClient,
    private readonly userId: number,
  ) {
    this.base = `/api/v1/internal/users/${userId}`;
  }

  async execute(toolCall: ToolCall): Promise<string> {
    const { name, input } = toolCall;

    try {
      switch (name) {
        case 'get_events':
          return await this.getEvents(input);
        case 'create_event':
          return await this.createEvent(input);
        case 'update_event':
          return await this.updateEvent(input);
        case 'cancel_event':
          return await this.cancelEvent(input);
        case 'snooze_event':
          return await this.snoozeEvent(input);
        case 'detach_event_reference':
          return await this.detachEventReference(input);
        default:
          return JSON.stringify({ error: `Unknown tool: ${name}` });
      }
    } catch (err) {
      log.error('tool execution failed', { tool: name, err });
      return JSON.stringify({ error: `Tool ${name} failed: ${String(err)}` });
    }
  }

  private async getEvents(input: Record<string, unknown>): Promise<string> {
    const params = new URLSearchParams();
    if (input.from)   params.set('from', String(input.from));
    if (input.to)     params.set('to', String(input.to));
    if (input.type)   params.set('type', String(input.type));
    if (input.status) params.set('status', String(input.status));

    const query = params.toString() ? `?${params.toString()}` : '';
    const res = await this.laravel.get(`${this.base}/events${query}`);
    return JSON.stringify(res);
  }

  private async createEvent(input: Record<string, unknown>): Promise<string> {
    const res = await this.laravel.post(`${this.base}/events`, input);
    return JSON.stringify(res);
  }

  private async updateEvent(input: Record<string, unknown>): Promise<string> {
    const { id, ...body } = input;
    const res = await this.laravel.patch(`${this.base}/events/${String(id)}`, body);
    return JSON.stringify(res);
  }

  private async cancelEvent(input: Record<string, unknown>): Promise<string> {
    const { id, ...body } = input;
    const res = await this.laravel.post(`${this.base}/events/${String(id)}/cancel`, body);
    return JSON.stringify(res);
  }

  private async snoozeEvent(input: Record<string, unknown>): Promise<string> {
    const { id, ...body } = input;
    const res = await this.laravel.post(`${this.base}/events/${String(id)}/snooze`, body);
    return JSON.stringify(res);
  }

  private async detachEventReference(input: Record<string, unknown>): Promise<string> {
    const res = await this.laravel.delete(`${this.base}/events/${String(input.event_id)}/reference`);
    return JSON.stringify(res);
  }
}
