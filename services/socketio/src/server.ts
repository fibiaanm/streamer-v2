/**
 * Socket.io service — PLACEHOLDER (etapa 01)
 *
 * Este servidor será reemplazado completamente en la etapa 05.
 * Por ahora levanta socket.io y acepta conexiones para que el
 * frontend (etapa 06) pueda probar la conectividad básica.
 *
 * TODO (etapa 05):
 *  - Verificación JWT con JWT_SECRET compartido
 *  - Consumidor Redis pub/sub para eventos de Laravel
 *  - Redis adapter para sync entre instancias
 *  - Scope rooms: workspace:{id}, room:{id}, enterprise:{id}
 *  - Manejo de subscribe/unsubscribe por scope
 */

import http from 'node:http';
import { Server } from 'socket.io';

const httpServer = http.createServer();

const io = new Server(httpServer, {
  cors: {
    origin: '*',
  },
});

io.on('connection', (socket) => {
  console.log(`[socket] connected   id=${socket.id}`);

  socket.emit('connected', {
    message: 'Socket.io placeholder — etapa 05 pendiente',
  });

  socket.on('disconnect', (reason) => {
    console.log(`[socket] disconnected id=${socket.id} reason=${reason}`);
  });
});

const PORT = parseInt(process.env.PORT ?? '3000', 10);

httpServer.listen(PORT, () => {
  console.log(`[socketio] Listening on port ${PORT}`);
});
