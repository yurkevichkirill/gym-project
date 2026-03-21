FROM node:24-alpine

WORKDIR /app

RUN corepack enable

COPY package.json pnpm-lock.yaml ./

RUN pnpm install && \
    pnpm config set store-dir /app/.pnpm-store

COPY . .

EXPOSE 3000

CMD ["pnpm", "dev"]