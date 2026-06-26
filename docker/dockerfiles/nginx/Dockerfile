# syntax=docker/dockerfile:1.7

FROM openresty/openresty:1.29.2.1-alpine AS prod

COPY symfony/public /var/www/public
COPY docker/conf/default.prod.conf.template /etc/nginx/templates/default.conf.template
COPY docker/dockerfiles/nginx/entrypoint.sh /usr/local/bin/nginx-entrypoint

RUN chmod 0755 /usr/local/bin/nginx-entrypoint && \
    mkdir -p /var/cache/nginx/fastcgi

ENTRYPOINT ["nginx-entrypoint"]
CMD ["openresty", "-g", "daemon off;"]
