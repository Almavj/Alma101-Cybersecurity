### Frontend Dockerfile (multi-stage)
# Builds the Vite React app and serves it with nginx
FROM node:18-alpine AS builder
WORKDIR /app

# Copy package manifest first to install deps
COPY package.json package-lock.json ./
RUN npm ci --silent

# Copy the rest of the source and build
COPY . .
# Allow overriding base path at build time with VITE_BASE_PATH env
ARG VITE_BASE_PATH=/
ENV VITE_BASE_PATH=${VITE_BASE_PATH}
RUN npm run build

FROM nginx:stable-alpine
COPY --from=builder /app/dist /usr/share/nginx/html

# Optional: custom nginx config (if provided) will be used
COPY docker/frontend/nginx.conf /etc/nginx/conf.d/default.conf

EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
