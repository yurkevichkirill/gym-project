FROM node:24-alpine

WORKDIR /app

ENV INTERNAL_API_URL=http://nginx:8080/api

RUN corepack enable

COPY package.json pnpm-lock.yaml ./

RUN pnpm config set store-dir /app/.pnpm-store && \
    pnpm install --frozen-lockfile --dangerously-allow-all-builds

COPY . .

EXPOSE 3000

CMD ["pnpm", "dev"]
