import { Socket } from 'socket.io';
import { logger } from '../logger';

// Skeleton — join/leave logic is completed in stage 10 when the Rooms domain exists.
export function registerRoomHandlers(socket: Socket): void {
  socket.on('join_room', ({ roomId }: { roomId: string }) => {
    // TODO (etapa 10): validate access via internal Laravel HTTP call, then:
    // - socket.join('room.' + roomId)
    // - manage presence in Redis
    logger.info('room.joined', { socket_id: socket.id, room_id: roomId });
  });

  socket.on('leave_room', ({ roomId }: { roomId: string }) => {
    socket.leave('room.' + roomId);
    logger.info('room.left', { socket_id: socket.id, room_id: roomId });
  });
}
