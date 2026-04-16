import { createClient } from 'redis';
import { Server } from 'socket.io';
import { config } from '../config';
import { logger } from '../logger';
import { redisChannelToRoom } from './channels';

const PATTERNS = ['user.*', 'room.*', 'workspace.*', 'enterprise.*'];

export async function startSubscriber(io: Server): Promise<void> {
  const client = createClient({
    socket: {
      host: config.REDIS_HOST,
      port: config.REDIS_PORT,
    },
  });

  await client.connect();

  for (const pattern of PATTERNS) {
    await client.pSubscribe(pattern, (payload, channel) => {
      try {
        const { event, data } = JSON.parse(payload) as { event: string; data: unknown };
        const room = redisChannelToRoom(channel);
        io.to(room).emit(event, data);
      } catch (err) {
        logger.error('redis.parse_error', { channel, error: (err as Error).message });
      }
    });

    logger.info('redis.subscribed', { channel: pattern });
  }
}
