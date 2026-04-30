import { Socket } from 'socket.io';
import { log } from '../logger';

export function registerAssistantHandlers(socket: Socket): void {
  socket.on('join_session', ({ sessionId }: { sessionId: string }) => {
    const room = `assistant.${sessionId}`;
    socket.join(room);
    log.info('assistant.session_joined', { socket_id: socket.id, room });
  });

  socket.on('leave_session', ({ sessionId }: { sessionId: string }) => {
    const room = `assistant.${sessionId}`;
    socket.leave(room);
    log.info('assistant.session_left', { socket_id: socket.id, room });
  });
}
