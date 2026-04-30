import { createClient } from 'redis';
import { log, withRequestId } from './logger';
import { LaravelClientImpl } from './api/LaravelClient';
import { LLMClientImpl } from './llm/LLMClient';
import { ConversationWorker } from './workers/ConversationWorker';

const QUEUE_KEY = 'assistant:jobs';

async function main(): Promise<void> {
  const redis = createClient({ url: process.env.REDIS_URL ?? 'redis://redis:6379' });

  redis.on('error', (err) => log.error('Redis error', { err }));

  await redis.connect();
  log.info('assistant-worker started', { queue: QUEUE_KEY });

  const laravel = new LaravelClientImpl(
    process.env.LARAVEL_INTERNAL_URL ?? 'http://php:9000',
    process.env.ASSISTANT_SERVICE_TOKEN ?? '',
  );

  const llm = new LLMClientImpl({
    anthropic: process.env.ANTHROPIC_API_KEY,
    openai:    process.env.OPENAI_API_KEY,
    gemini:    process.env.GEMINI_API_KEY,
  });

  const conversationWorker = new ConversationWorker(llm, laravel);

  // eslint-disable-next-line no-constant-condition
  while (true) {
    const raw = await redis.brPop(QUEUE_KEY, 5);
    if (!raw) continue;

    try {
      const job = JSON.parse(raw.element) as { type: string; request_id?: string; [key: string]: unknown };
      log.info('job received', { type: job.type });

      const process = async (): Promise<void> => {
        if (job.type === 'process_message') {
          await conversationWorker.process(job as Parameters<typeof conversationWorker.process>[0]);
        } else {
          log.warn('unknown job type', { type: job.type });
        }
      };

      await (job.request_id ? withRequestId(job.request_id, process) : process());
    } catch (err) {
      log.error('failed to process job', { raw: raw.element, err });
    }
  }
}

main().catch((err) => {
  log.error('fatal', { err });
  process.exit(1);
});
