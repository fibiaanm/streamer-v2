FROM node:20-alpine

WORKDIR /app

# Install dependencies first (layer cache)
COPY services/socketio/package*.json ./
RUN npm install

# Copy source
COPY services/socketio/src/ ./src/
COPY services/socketio/tsconfig.json ./

EXPOSE 3000

CMD ["npm", "start"]
