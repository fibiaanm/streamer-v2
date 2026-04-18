import http from 'node:http';
import { Server } from 'socket.io';
import { createClient } from 'redis';
import { createAdapter } from '@socket.io/redis-adapter';
import { config } from './config';
import { logger } from './logger';
import { authMiddleware } from './middleware/auth';
import { startSubscriber } from './redis/subscriber';
import { registerRoomHandlers } from './handlers/roomHandler';
import { registerPresenceHandlers } from './handlers/presenceHandler';
import { registerEnterpriseHandlers } from './handlers/enterpriseHandler';

const httpServer = http.createServer();

const io = new Server(httpServer, {
  cors: {
    origin: true,
    credentials: true,
  },
});

io.use(authMiddleware);

io.on('connection', (socket) => {
  const userId = socket.data.user.sub as number;
  socket.join('user.' + userId);

  logger.info('socket.connected', { socket_id: socket.id, user_id: userId });

  registerRoomHandlers(socket);
  registerPresenceHandlers(socket);
  registerEnterpriseHandlers(socket);

  socket.on('disconnect', (reason) => {
    logger.info('socket.disconnected', { socket_id: socket.id, reason });
  });
});

async function bootstrap(): Promise<void> {
  const pubClient = createClient({
    socket: { host: config.REDIS_HOST, port: config.REDIS_PORT },
  });
  const subClient = pubClient.duplicate();

  await Promise.all([pubClient.connect(), subClient.connect()]);
  io.adapter(createAdapter(pubClient, subClient));

  await startSubscriber(io);

  httpServer.listen(config.PORT, () => {
    logger.info('server.started', { port: config.PORT });
  });
}

bootstrap().catch((err) => {
  logger.error('server.fatal', { error: (err as Error).message });
  process.exit(1);
});
