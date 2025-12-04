FROM --platform=$BUILDPLATFORM node:20-alpine AS base

WORKDIR /app

# Copy package files
COPY package*.json ./

FROM --platform=$BUILDPLATFORM base AS dependencies

# Install all dependencies
RUN npm ci

FROM --platform=$BUILDPLATFORM base AS dev

# Install dependencies
COPY --from=dependencies /app/node_modules ./node_modules

# Copy application files
COPY . .

# Expose Vite dev server port
EXPOSE 3000

# Start development server
CMD ["npm", "run", "dev", "--", "--host"]

FROM --platform=$BUILDPLATFORM base AS builder

# Install dependencies
COPY --from=dependencies /app/node_modules ./node_modules

# Copy application files
COPY . .

# Build the application
RUN npm run build

FROM --platform=$BUILDPLATFORM nginx:alpine AS production

# Copy custom nginx configuration
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

# Copy built assets from builder stage
COPY --from=builder /app/dist /usr/share/nginx/html

# Expose port
EXPOSE 80

# Start nginx
CMD ["nginx", "-g", "daemon off;"]
