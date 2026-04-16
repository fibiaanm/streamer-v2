import { describe, it, expect, vi, beforeAll } from 'vitest';
import jwt from 'jsonwebtoken';

const SECRET = 'test-secret';

vi.mock('../src/config', () => ({
  config: {
    JWT_SECRET: SECRET,
    PORT: 3000,
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

function makeSocket(token?: string) {
  return {
    id: 'test-socket-id',
    handshake: { auth: token !== undefined ? { token } : {} },
    data: {} as { user?: object },
  };
}

describe('authMiddleware', () => {
  it('acepta un JWT válido', () => {
    const token = jwt.sign(
      { sub: 1, name: 'Test User', email: 'test@example.com' },
      SECRET,
      { expiresIn: '1h' },
    );
    const socket = makeSocket(`Bearer ${token}`);
    const next = vi.fn();

    authMiddleware(socket as any, next);

    expect(next).toHaveBeenCalledWith();
    expect((socket.data.user as any).sub).toBe(1);
    expect((socket.data.user as any).email).toBe('test@example.com');
  });

  it('acepta token sin prefijo Bearer', () => {
    const token = jwt.sign({ sub: 2, name: 'Test', email: 'test2@example.com' }, SECRET);
    const socket = makeSocket(token);
    const next = vi.fn();

    authMiddleware(socket as any, next);

    expect(next).toHaveBeenCalledWith();
  });

  it('rechaza cuando falta el token', () => {
    const socket = makeSocket();
    const next = vi.fn();

    authMiddleware(socket as any, next);

    expect(next).toHaveBeenCalledWith(expect.any(Error));
    expect((next.mock.calls[0][0] as Error).message).toBe('authentication_required');
  });

  it('rechaza un token con firma inválida', () => {
    const token = jwt.sign({ sub: 1 }, 'wrong-secret');
    const socket = makeSocket(`Bearer ${token}`);
    const next = vi.fn();

    authMiddleware(socket as any, next);

    expect(next).toHaveBeenCalledWith(expect.any(Error));
    expect((next.mock.calls[0][0] as Error).message).toBe('invalid_token');
  });

  it('rechaza un token expirado', () => {
    const token = jwt.sign({ sub: 1 }, SECRET, { expiresIn: '-1s' });
    const socket = makeSocket(`Bearer ${token}`);
    const next = vi.fn();

    authMiddleware(socket as any, next);

    expect(next).toHaveBeenCalledWith(expect.any(Error));
    expect((next.mock.calls[0][0] as Error).message).toBe('invalid_token');
  });

  it('rechaza un string malformado', () => {
    const socket = makeSocket('Bearer not.a.valid.jwt');
    const next = vi.fn();

    authMiddleware(socket as any, next);

    expect(next).toHaveBeenCalledWith(expect.any(Error));
    expect((next.mock.calls[0][0] as Error).message).toBe('invalid_token');
  });
});
