FROM node:20-alpine

WORKDIR /app

# Install dependencies first (layer cache)
COPY services/assistant-worker/package*.json ./
RUN npm install

# Copy source
COPY services/assistant-worker/src/ ./src/
COPY services/assistant-worker/tsconfig.json ./

CMD ["npm", "start"]
