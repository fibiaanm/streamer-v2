import { describe, it, expect, vi, beforeAll, afterAll } from 'vitest';
import { createServer as createHttpServer } from 'http';
import { Server } from 'socket.io';
import { io as ioClient } from 'socket.io-client';
import jwt from 'jsonwebtoken';

const SECRET = 'test-secret';

vi.mock('../src/config', () => ({
  config: {
    JWT_SECRET: SECRET,
    PORT: 0,
    REDIS_HOST: 'localhost',
    REDIS_PORT: 6379,
    LOG_LEVEL: 'silent',
    NODE_ENV: 'test',
  },
}));

vi.mock('../src/logger', () => ({
  logger: {
    warn: vi.fn(),
    info: vi.fn(),
    error: vi.fn(),
  },
}));

const { authMiddleware } = await import('../src/middleware/auth');

describe('Socket.io connection', () => {
  let httpServer: ReturnType<typeof createHttpServer>;
  let io: Server;
  let address: string;

  beforeAll(async () => {
    httpServer = createHttpServer();
    io = new Server(httpServer, { cors: { origin: '*' } });
    io.use(authMiddleware);

    io.on('connection', (socket) => {
      socket.on('disconnect', () => {});
    });

    await new Promise<void>((resolve) => {
      httpServer.listen(0, () => resolve());
    });

    const addr = httpServer.address() as { port: number };
    address = `http://localhost:${addr.port}`;
  });

  afterAll(async () => {
    await new Promise<void>((resolve) => io.close(() => resolve()));
    await new Promise<void>((resolve) => httpServer.close(() => resolve()));
  });

  it('conecta con JWT válido', async () => {
    const token = jwt.sign(
      { sub: 1, name: 'Test User', email: 'test@example.com' },
      SECRET,
      { expiresIn: '1h' },
    );

    await new Promise<void>((resolve, reject) => {
      const client = ioClient(address, {
        auth: { token: `Bearer ${token}` },
        reconnection: false,
      });
      client.on('connect', () => { client.disconnect(); resolve(); });
      client.on('connect_error', (err) => reject(err));
    });
  });

  it('rechaza conexión sin token', async () => {
    await new Promise<void>((resolve, reject) => {
      const client = ioClient(address, { auth: {}, reconnection: false });
      client.on('connect', () => { client.disconnect(); reject(new Error('No debería conectar')); });
      client.on('connect_error', (err) => {
        expect(err.message).toBe('authentication_required');
        resolve();
      });
    });
  });

  it('rechaza conexión con JWT inválido', async () => {
    await new Promise<void>((resolve, reject) => {
      const client = ioClient(address, {
        auth: { token: 'Bearer invalid.token.here' },
        reconnection: false,
      });
      client.on('connect', () => { client.disconnect(); reject(new Error('No debería conectar')); });
      client.on('connect_error', (err) => {
        expect(err.message).toBe('invalid_token');
        resolve();
      });
    });
  });

  it('desconecta correctamente', async () => {
    const token = jwt.sign({ sub: 1, name: 'Test', email: 'test@example.com' }, SECRET);

    await new Promise<void>((resolve, reject) => {
      const client = ioClient(address, {
        auth: { token: `Bearer ${token}` },
        reconnection: false,
      });
      client.on('connect', () => client.disconnect());
      client.on('disconnect', () => resolve());
      client.on('connect_error', (err) => reject(err));
    });
  });
});
