import type { LaravelClient } from '../../api/LaravelClient';
import type { ToolCall } from '../../llm/types';
import { log } from '../../logger';

interface EventSummary {
  id:       string;
  content:  string;
  event_at: string;
  type:     string;
  status:   string;
  reminders?: number;
}

function projectEvent(e: Record<string, unknown>): EventSummary {
  const reminders = Array.isArray(e.reminders) ? e.reminders.length : undefined;
  return {
    id:       String(e.id ?? ''),
    content:  String(e.content ?? ''),
    event_at: String(e.event_at ?? ''),
    type:     String(e.type ?? ''),
    status:   String(e.status ?? ''),
    ...(reminders !== undefined ? { reminders } : {}),
  };
}

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
        case 'get_lists':
          return await this.getLists(input);
        case 'get_list':
          return await this.getList(input);
        case 'create_list':
          return await this.createList(input);
        case 'delete_list':
          return await this.deleteList(input);
        case 'add_to_list':
          return await this.addToList(input);
        case 'update_list_item':
          return await this.updateListItem(input);
        case 'remove_from_list':
          return await this.removeFromList(input);
        case 'clear_completed_items':
          return await this.clearCompletedItems(input);
        case 'send_options':
          return JSON.stringify({ __virtual__: 'send_options', ...input });
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
    const res   = await this.laravel.get(`${this.base}/events${query}`) as { data: Record<string, unknown>[] };
    const events = Array.isArray(res?.data) ? res.data.map(projectEvent) : [];
    return JSON.stringify({ data: events });
  }

  private async createEvent(input: Record<string, unknown>): Promise<string> {
    const res = await this.laravel.post(`${this.base}/events`, input) as { data: Record<string, unknown> };
    return JSON.stringify({ data: projectEvent(res?.data ?? {}) });
  }

  private async updateEvent(input: Record<string, unknown>): Promise<string> {
    const { id, ...body } = input;
    const res = await this.laravel.patch(`${this.base}/events/${String(id)}`, body) as { data: Record<string, unknown> };
    return JSON.stringify({ data: projectEvent(res?.data ?? {}) });
  }

  private async cancelEvent(input: Record<string, unknown>): Promise<string> {
    const { id, ...body } = input;
    const res = await this.laravel.post(`${this.base}/events/${String(id)}/cancel`, body) as { data: Record<string, unknown> };
    return JSON.stringify({ data: projectEvent(res?.data ?? {}) });
  }

  private async snoozeEvent(input: Record<string, unknown>): Promise<string> {
    const { id, ...body } = input;
    const res = await this.laravel.post(`${this.base}/events/${String(id)}/snooze`, body) as { data: Record<string, unknown> };
    return JSON.stringify({ data: projectEvent(res?.data ?? {}) });
  }

  private async detachEventReference(input: Record<string, unknown>): Promise<string> {
    const res = await this.laravel.delete(`${this.base}/events/${String(input.event_id)}/reference`) as { data: Record<string, unknown> };
    return JSON.stringify({ data: projectEvent(res?.data ?? {}) });
  }

  private async getLists(input: Record<string, unknown>): Promise<string> {
    const params = new URLSearchParams();
    if (input.include_shared) params.set('include_shared', 'true');
    const query = params.toString() ? `?${params.toString()}` : '';
    const res = await this.laravel.get(`${this.base}/lists${query}`) as { data: unknown[] };
    return JSON.stringify({ data: res?.data ?? [] });
  }

  private async getList(input: Record<string, unknown>): Promise<string> {
    const res = await this.laravel.get(`${this.base}/lists/${String(input.id)}`) as { data: unknown };
    return JSON.stringify({ data: res?.data ?? {} });
  }

  private async createList(input: Record<string, unknown>): Promise<string> {
    const res = await this.laravel.post(`${this.base}/lists`, input) as { data: unknown };
    return JSON.stringify({ data: res?.data ?? {} });
  }

  private async deleteList(input: Record<string, unknown>): Promise<string> {
    await this.laravel.delete(`${this.base}/lists/${String(input.id)}`);
    return JSON.stringify({ success: true });
  }

  private async addToList(input: Record<string, unknown>): Promise<string> {
    const { id, items } = input;
    const res = await this.laravel.post(`${this.base}/lists/${String(id)}/items`, { items }) as { data: unknown[] };
    return JSON.stringify({ data: res?.data ?? [] });
  }

  private async updateListItem(input: Record<string, unknown>): Promise<string> {
    const { list_id, item_id, ...body } = input;
    const res = await this.laravel.patch(`${this.base}/lists/${String(list_id)}/items/${String(item_id)}`, body) as { data: unknown };
    return JSON.stringify({ data: res?.data ?? {} });
  }

  private async removeFromList(input: Record<string, unknown>): Promise<string> {
    await this.laravel.delete(`${this.base}/lists/${String(input.list_id)}/items/${String(input.item_id)}`);
    return JSON.stringify({ success: true });
  }

  private async clearCompletedItems(input: Record<string, unknown>): Promise<string> {
    await this.laravel.delete(`${this.base}/lists/${String(input.id)}/items/completed`);
    return JSON.stringify({ success: true });
  }
}
