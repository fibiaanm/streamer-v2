// Maps a Redis channel name to the corresponding Socket.io room name.
// Currently a 1:1 mapping — both names are identical.
// Centralised here so any future divergence has a single change point.
export function redisChannelToRoom(channel: string): string {
  return channel;
}
