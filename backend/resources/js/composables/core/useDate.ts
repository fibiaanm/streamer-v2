import { computed } from 'vue'
import { useSession } from './useSession'

export const useDate = () => {
  const { user } = useSession()
  const tz = computed(() => user.value?.timezone ?? 'UTC')

  // sáb, 9 may, 9:00 — event cards, full datetime
  function formatEventAt(iso: string): string {
    return new Date(iso).toLocaleString('es', {
      timeZone: tz.value,
      weekday: 'short', day: 'numeric', month: 'short',
      hour: '2-digit', minute: '2-digit',
    })
  }

  // 9 may, 9:00 — reminder fire_at, compact datetime
  function formatDatetime(iso: string): string {
    return new Date(iso).toLocaleString('es', {
      timeZone: tz.value,
      day: 'numeric', month: 'short',
      hour: '2-digit', minute: '2-digit',
    })
  }

  // 9:00 — message timestamps, time-only slots
  function formatTime(iso: string): string {
    return new Date(iso).toLocaleTimeString('es', {
      timeZone: tz.value,
      hour: '2-digit', minute: '2-digit',
    })
  }

  // 9 may — session list, member invitations, short labels
  function formatShortDate(iso: string): string {
    return new Date(iso).toLocaleDateString('es', {
      timeZone: tz.value,
      day: 'numeric', month: 'short',
    })
  }

  // 9 may, 9:00:00 — Unix timestamps (seconds) from jobs queue
  function formatTimestamp(ts: number): string {
    return new Date(ts * 1000).toLocaleString('es', {
      timeZone: tz.value,
      day: 'numeric', month: 'short',
      hour: '2-digit', minute: '2-digit', second: '2-digit',
    })
  }

  // YYYY-MM-DD in user timezone — for API range params (from/to)
  function isoDate(d: Date = new Date()): string {
    return new Intl.DateTimeFormat('en-CA', { timeZone: tz.value }).format(d)
  }

  return { formatEventAt, formatDatetime, formatTime, formatShortDate, formatTimestamp, isoDate }
}
