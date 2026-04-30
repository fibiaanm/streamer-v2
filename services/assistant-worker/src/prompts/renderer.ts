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

export function renderSystemPrompt(user: UserContext, memories: MemoryEntry[]): Promise<string> {
  return new Promise((resolve, reject) => {
    env.render('system.njk', { user, memories }, (err, result) => {
      if (err) reject(err);
      else resolve(result ?? '');
    });
  });
}

export function renderMemoryPrompt(
  messages: Array<{ role: string; content: string }>,
  memories: MemoryEntry[],
): Promise<string> {
  return new Promise((resolve, reject) => {
    env.render('memory-worker.njk', { messages, memories }, (err, result) => {
      if (err) reject(err);
      else resolve(result ?? '');
    });
  });
}
