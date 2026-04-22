import { createClient } from 'redis';
import { createLogger, format, transports } from 'winston';

const log = createLogger({
  level: process.env.LOG_LEVEL ?? 'info',
  format: format.combine(format.timestamp(), format.json()),
  transports: [new transports.Console()],
});

const QUEUE_KEY = 'assistant:jobs';

async function main(): Promise<void> {
  const redis = createClient({ url: process.env.REDIS_URL ?? 'redis://redis:6379' });

  redis.on('error', (err) => log.error('Redis error', { err }));

  await redis.connect();
  log.info('assistant-worker started', { queue: QUEUE_KEY });

  // eslint-disable-next-line no-constant-condition
  while (true) {
    const raw = await redis.brPop(QUEUE_KEY, 5);
    if (!raw) continue;

    try {
      const job = JSON.parse(raw.element) as { type: string; payload: unknown };
      log.info('job received', { type: job.type });
      // TODO: route to handler in stage 02
    } catch (err) {
      log.error('failed to process job', { raw: raw.element, err });
    }
  }
}

main().catch((err) => {
  log.error('fatal', { err });
  process.exit(1);
});
