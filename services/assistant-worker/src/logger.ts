import { AsyncLocalStorage } from 'async_hooks';
import winston from 'winston';
import Transport from 'winston-transport';
import { Client } from '@opensearch-project/opensearch';

const today = () => new Date().toISOString().slice(0, 10).replace(/-/g, '.');

// Per-job context — propagates automatically through async call chains
const contextStore = new AsyncLocalStorage<{ request_id: string }>();

export function withRequestId<T>(requestId: string, fn: () => Promise<T>): Promise<T> {
  return contextStore.run({ request_id: requestId }, fn);
}

export function getRequestId(): string | undefined {
  return contextStore.getStore()?.request_id;
}

// Normalize values before JSON.stringify: Error objects become {} otherwise.
// No stack traces — large strings cause OpenSearch to silently reject documents.
function normalizeForJson(val: unknown): unknown {
  if (val instanceof Error) {
    return { name: val.name, message: val.message };
  }
  if (Array.isArray(val)) {
    return val.map(normalizeForJson);
  }
  if (val !== null && typeof val === 'object') {
    const out: Record<string, unknown> = {};
    for (const [k, v] of Object.entries(val)) {
      out[k] = normalizeForJson(v);
    }
    return out;
  }
  return val;
}

function buildMeta(data?: Record<string, unknown>): Record<string, unknown> {
  if (!data || Object.keys(data).length === 0) return {};
  return { data: JSON.stringify(normalizeForJson(data)) };
}

class OpenSearchTransport extends Transport {
  private readonly client: Client;
  private readonly index: string;

  constructor(host: string, index: string) {
    super();
    this.client = new Client({ node: host });
    this.index = index;
  }

  log(info: Record<string, unknown>, callback: () => void): void {
    const { level, message, data } = info;
    const context = contextStore.getStore();

    const doc: Record<string, unknown> = {
      '@timestamp': new Date().toISOString(),
      service:      'assistant-worker',
      level,
      message,
    };
    if (data !== undefined)           doc['data']       = data;
    if (context?.request_id)          doc['request_id'] = context.request_id;

    this.client.index({
      index: `${this.index}-${today()}`,
      body:  doc,
    }).catch(() => {
      // silent fail
    });

    callback();
  }
}

const winstonLogger = winston.createLogger({
  level: process.env.LOG_LEVEL ?? 'info',
  format: winston.format.combine(winston.format.timestamp(), winston.format.json()),
  transports: [new winston.transports.Console()],
});

if (process.env.OPENSEARCH_HOST) {
  winstonLogger.add(new OpenSearchTransport(
    process.env.OPENSEARCH_HOST,
    process.env.OPENSEARCH_INDEX ?? 'streamerv2-assistant',
  ));
}

// Typed wrapper: first arg is the event name, second is structured data (serialized to JSON string in OS)
export const log = {
  info:  (message: string, data?: Record<string, unknown>) => winstonLogger.info(message,  buildMeta(data)),
  warn:  (message: string, data?: Record<string, unknown>) => winstonLogger.warn(message,  buildMeta(data)),
  error: (message: string, data?: Record<string, unknown>) => winstonLogger.error(message, buildMeta(data)),
  debug: (message: string, data?: Record<string, unknown>) => winstonLogger.debug(message, buildMeta(data)),
};
