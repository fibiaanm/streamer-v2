export const config = {
  PORT: parseInt(process.env.PORT ?? '3000', 10),
  JWT_SECRET: process.env.JWT_SECRET ?? '',
  REDIS_HOST: process.env.REDIS_HOST ?? 'redis',
  REDIS_PORT: parseInt(process.env.REDIS_PORT ?? '6379', 10),
  LOG_LEVEL: process.env.LOG_LEVEL ?? 'info',
  NODE_ENV: process.env.NODE_ENV ?? 'development',
};
