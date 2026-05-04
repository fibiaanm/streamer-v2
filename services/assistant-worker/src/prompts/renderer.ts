import { join } from 'path';
import nunjucks from 'nunjucks';

const env = nunjucks.configure(join(__dirname), { autoescape: false });

interface UserContext {
  name: string;
  timezone: string;
  defaultCurrency: string;
}

interface MemoryEntry {
  category: string;
  description: string;
  content: string;
}

function formatDatetimeInTimezone(timezone: string, now: Date): string {
  return new Intl.DateTimeFormat('es-ES', {
    timeZone: timezone,
    dateStyle: 'full',
    timeStyle: 'short',
  }).format(now);
}

function formatDateInTimezone(timezone: string, now: Date): string {
  return new Intl.DateTimeFormat('es-ES', {
    timeZone: timezone,
    dateStyle: 'full',
  }).format(now);
}

export function renderSystemPrompt(user: UserContext, memories: MemoryEntry[], now: Date): Promise<string> {
  return new Promise((resolve, reject) => {
    env.render('system.njk', {
      user,
      memories,
      current_datetime:   formatDatetimeInTimezone(user.timezone, now),
      effective_timezone: user.timezone,
      timezone_override:  null,
    }, (err, result) => {
      if (err) reject(err);
      else resolve(result ?? '');
    });
  });
}

export function renderMemoryPrompt(
  user: UserContext,
  messages: Array<{ role: string; content: string; created_at?: string }>,
  memories: MemoryEntry[],
): Promise<string> {
  return new Promise((resolve, reject) => {
    env.render('memory-worker.njk', {
      user,
      messages,
      memories,
      current_date: formatDateInTimezone(user.timezone, new Date()),
    }, (err, result) => {
      if (err) reject(err);
      else resolve(result ?? '');
    });
  });
}
