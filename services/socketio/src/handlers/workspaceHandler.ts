import { Socket } from 'socket.io';
import { log } from '../logger';

export function registerWorkspaceHandlers(socket: Socket): void {
  socket.on('join_workspace', ({ workspaceId, roleId }: { workspaceId: string; roleId: string }) => {
    const wsRoom   = `workspace.${workspaceId}`;
    const roleRoom = `workspace.${workspaceId}.role.${roleId}`;
    socket.join(wsRoom);
    socket.join(roleRoom);
    log.info('workspace.joined', { socket_id: socket.id, ws_room: wsRoom, role_room: roleRoom });
  });

  socket.on('leave_workspace', ({ workspaceId, roleId }: { workspaceId: string; roleId: string }) => {
    const wsRoom   = `workspace.${workspaceId}`;
    const roleRoom = `workspace.${workspaceId}.role.${roleId}`;
    socket.leave(wsRoom);
    socket.leave(roleRoom);
    log.info('workspace.left', { socket_id: socket.id, ws_room: wsRoom, role_room: roleRoom });
  });
}
