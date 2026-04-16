import winston from 'winston';
import { config } from './config';

export const logger = winston.createLogger({
  level: config.LOG_LEVEL,
  format: winston.format.json(),
  defaultMeta: { service: 'socketio' },
  transports: [new winston.transports.Console()],
});
