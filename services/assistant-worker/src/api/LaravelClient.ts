import { getRequestId } from '../logger';

export interface LaravelClient {
  get(url: string): Promise<{ data: unknown }>;
  post(url: string, body: unknown): Promise<unknown>;
  patch(url: string, body: unknown): Promise<unknown>;
  delete(url: string): Promise<unknown>;
}

export class LaravelClientImpl implements LaravelClient {
  constructor(
    private readonly baseUrl: string,
    private readonly serviceToken: string,
  ) {}

  private async request(method: string, url: string, body?: unknown): Promise<{ data: unknown }> {
    const headers: Record<string, string> = {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${this.serviceToken}`,
    };

    const requestId = getRequestId();
    if (requestId) {
      headers['X-Request-Id'] = requestId;
    }

    const res = await fetch(`${this.baseUrl}${url}`, {
      method,
      headers,
      body: body !== undefined ? JSON.stringify(body) : undefined,
    });

    if (!res.ok) {
      throw new Error(`Laravel request failed: ${method} ${url} → ${res.status}`);
    }

    return res.json();
  }

  get(url: string): Promise<{ data: unknown }> {
    return this.request('GET', url) as Promise<{ data: unknown }>;
  }

  post(url: string, body: unknown): Promise<unknown> {
    return this.request('POST', url, body);
  }

  patch(url: string, body: unknown): Promise<unknown> {
    return this.request('PATCH', url, body);
  }

  delete(url: string): Promise<unknown> {
    return this.request('DELETE', url);
  }
}
