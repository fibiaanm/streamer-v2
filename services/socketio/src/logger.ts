import winston from 'winston';
import Transport from 'winston-transport';
import { Client } from '@opensearch-project/opensearch';
import { config } from './config';

const today = () => new Date().toISOString().slice(0, 10).replace(/-/g, '.');

class OpenSearchTransport extends Transport {
  private readonly client: Client;
  private readonly index: string;

  constructor(host: string, index: string) {
    super();
    this.client = new Client({ node: host });
    this.index = index;
  }

  log(info: Record<string, unknown>, callback: () => void): void {
    try {
      this.client.index({
        index: `${this.index}-${today()}`,
        body: {
          '@timestamp': new Date().toISOString(),
          service:      'socketio',
          level:        info['level'],
          message:      info['message'],
          context:      info['context'] ?? {},
        },
      });
    } catch {
      // silent fail — OpenSearch down does not affect the service
    }
    callback();
  }
}

export const logger = winston.createLogger({
  level: config.LOG_LEVEL,
  format: winston.format.json(),
  defaultMeta: { service: 'socketio' },
  transports: [new winston.transports.Console()],
});

if (process.env.OPENSEARCH_HOST) {
  logger.add(new OpenSearchTransport(
    process.env.OPENSEARCH_HOST,
    process.env.OPENSEARCH_INDEX ?? 'streamer-logs',
  ));
}
