FROM nginx:1.29.5-alpine AS builder

RUN apk add --no-cache \
    build-base \
    pcre-dev \
    zlib-dev \
    linux-headers \
    curl \
    git

RUN curl -L -o nginx.tar.gz "http://nginx.org/download/nginx-1.29.5.tar.gz" \
    && tar xzf nginx.tar.gz \
    && git clone https://github.com/nginx-modules/ngx_cache_purge.git ngx_cache_purge \
    && rm -rf nginx.tar.gz

WORKDIR /nginx-1.29.5
RUN ./configure \
    --with-compat \
    --add-dynamic-module=../ngx_cache_purge \
    && make modules

FROM nginx:1.29.5-alpine
COPY --from=builder /nginx-1.29.5/objs/ngx_http_cache_purge_module.so /usr/lib/nginx/modules/
RUN sed -i '1iload_module /usr/lib/nginx/modules/ngx_http_cache_purge_module.so;' /etc/nginx/nginx.conf

COPY docker/conf/default.conf /etc/nginx/conf.d/default.conf
