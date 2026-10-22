# syntax=docker/dockerfile:1

# ---- Tahap 1: bangun aplikasi Vue dengan Node.js ----
FROM node:22-alpine3.21 AS build

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY . .

# Alamat API relatif. Nginx yang meneruskan /api ke service backend.
ARG VITE_API_URL=/api
ENV VITE_API_URL=$VITE_API_URL

RUN npm run build

# ---- Tahap 2: sajikan hasil build dengan Nginx ----
# Image akhir hanya berisi file statis hasil build (tanpa node_modules).
FROM nginx:1.27-alpine

COPY nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=build /app/dist /usr/share/nginx/html

EXPOSE 80