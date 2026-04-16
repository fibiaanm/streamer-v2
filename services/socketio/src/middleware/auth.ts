import { Socket } from 'socket.io';
import jwt from 'jsonwebtoken';
import { config } from '../config';
import { logger } from '../logger';

export interface SocketUser {
  sub: number;
  name: string;
  email: string;
  iat: number;
  exp: number;
}

export function authMiddleware(socket: Socket, next: (err?: Error) => void): void {
  const raw = socket.handshake.auth?.token as string | undefined;
  const token = raw?.replace(/^Bearer\s+/i, '');

  if (!token) {
    logger.warn('auth.token_missing', { socket_id: socket.id });
    return next(new Error('authentication_required'));
  }

  try {
    const payload = jwt.verify(token, config.JWT_SECRET) as unknown as SocketUser;
    socket.data.user = payload;
    next();
  } catch (err) {
    logger.warn('auth.token_invalid', { socket_id: socket.id, error: (err as Error).message });
    next(new Error('invalid_token'));
  }
}
