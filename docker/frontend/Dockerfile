# Frontend Dockerfile (multi-stage)
# Builds the Vite React app and serves it with nginx.
# This version includes a placeholder for decrypting encrypted assets before build.

FROM node:18-alpine as build
WORKDIR /app

# Install dependencies
COPY package.json package-lock.json ./
RUN npm ci --silent

# Copy the rest of the source including encrypted assets
COPY . .

# If your assets are encrypted, decrypt them here before the build step.
# Example placeholder (replace with your actual decryption command and secret handling):
# RUN if [ -f ./scripts/decrypt_assets.sh ]; then sh ./scripts/decrypt_assets.sh; fi
# Or, use a tool/secret injected via build-time secrets or CI (recommended for production).

# Build-time ARGs for Vite environment variables (Vite inlines VITE_* values at build time)
ARG VITE_SUPABASE_URL
ARG VITE_SUPABASE_PUBLISHABLE_KEY

# Expose those ARGs as ENVs so the build sees them during `npm run build`
ENV VITE_SUPABASE_URL=${VITE_SUPABASE_URL}
ENV VITE_SUPABASE_PUBLISHABLE_KEY=${VITE_SUPABASE_PUBLISHABLE_KEY}

# Allow overriding base path at build time with VITE_BASE_PATH
ARG VITE_BASE_PATH=/
ENV VITE_BASE_PATH=${VITE_BASE_PATH}

RUN npm run build

FROM nginx:stable-alpine
COPY --from=build /app/dist /usr/share/nginx/html

# Use provided nginx config that proxies /api to backend when running in Docker
COPY docker/frontend/nginx.conf /etc/nginx/conf.d/default.conf

EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
