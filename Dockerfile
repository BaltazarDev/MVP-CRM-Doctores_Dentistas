FROM node:18-alpine

WORKDIR /app

# Copy backend dependencies
COPY backend/package*.json ./backend/

# Install dependencies
WORKDIR /app/backend
RUN npm install cnpm -g && npm install

# Copy source code
WORKDIR /app
COPY backend ./backend
COPY frontend ./frontend

# Expose port
EXPOSE 3000

# Start server
WORKDIR /app/backend
CMD ["node", "server.js"]
