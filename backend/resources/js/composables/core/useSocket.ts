import { shallowRef } from 'vue'
import { io } from 'socket.io-client'
import type { Socket } from 'socket.io-client'
import { createSharedComposableById } from './createSharedComposableById'

type EventHandler = (...args: unknown[]) => void

export const useSocket = () => createSharedComposableById('socket', () => {
  const connected = shallowRef(false)
  const socket    = shallowRef<Socket | null>(null)

  const connect = (token: string) => {
    if (socket.value) return

    const instance = io(import.meta.env.VITE_SOCKET_URL as string, {
      auth: { token: `Bearer ${token}` },
      reconnectionAttempts: 5,
    })

    instance.on('connect',    () => { connected.value = true })
    instance.on('disconnect', () => { connected.value = false })

    socket.value = instance
  }

  const disconnect = () => {
    socket.value?.disconnect()
    socket.value    = null
    connected.value = false
  }

  const emit = (event: string, data?: unknown) => {
    socket.value?.emit(event, data)
  }

  const on = (event: string, handler: EventHandler) => {
    socket.value?.on(event, handler)
  }

  const off = (event: string, handler: EventHandler) => {
    socket.value?.off(event, handler)
  }

  return { connected, socket, connect, disconnect, emit, on, off }
})
