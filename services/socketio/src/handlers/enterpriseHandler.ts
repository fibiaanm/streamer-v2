import { Socket } from 'socket.io';
import { logger } from '../logger';

export function registerEnterpriseHandlers(socket: Socket): void {
  socket.on('join_enterprise', ({ enterpriseId }: { enterpriseId: string }) => {
    const room = `enterprise.${enterpriseId}`;
    socket.join(room);
    logger.info('enterprise.joined', { socket_id: socket.id, room });
  });

  socket.on('leave_enterprise', ({ enterpriseId }: { enterpriseId: string }) => {
    const room = `enterprise.${enterpriseId}`;
    socket.leave(room);
    logger.info('enterprise.left', { socket_id: socket.id, room });
  });
}
