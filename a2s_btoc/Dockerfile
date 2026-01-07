# Build stage
FROM node:20-alpine AS builder

WORKDIR /app

# Copy package files
COPY package*.json ./

# Install dependencies
RUN npm ci

# Set build-time environment variables
ARG VITE_API_BASE_URL
ENV VITE_API_BASE_URL=$VITE_API_BASE_URL

# Copy source code
COPY . .

# Build the application
RUN npm run build

# Production stage
FROM node:20-alpine

WORKDIR /app

# Copy package files
COPY package*.json ./

# Install production dependencies only
RUN npm ci --only=production

# Copy built assets from builder stage
COPY --from=builder /app/dist ./dist

# Copy server code
COPY server ./server

# Copy Netlify functions (if needed for local testing)
COPY netlify ./netlify

# Expose ports
# 5173 for Vite dev server (if running in dev mode)
# 3000 for Express server (or port defined in SERVER_PORT)
EXPOSE 3000

# Set environment to production
ENV NODE_ENV=production

# Start the Express server
CMD ["node", "server/index.js"]
