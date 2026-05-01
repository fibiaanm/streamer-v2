import { log } from './logger';

export function report(err: unknown, context?: Record<string, unknown>): void {
  log.error('reported_error', { ...context, err });

  // TODO: forward to external observability (Sentry, Bugsnag, etc.)
  // notifyProvider(err, context);
}
