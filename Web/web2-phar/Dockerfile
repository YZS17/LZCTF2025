FROM php:7.2.0-apache

# 配置PHP以支持phar反序列化
RUN echo "phar.readonly = Off" >> /usr/local/etc/php/php.ini && \
    echo "allow_url_include = On" >> /usr/local/etc/php/php.ini && \
    echo "allow_url_fopen = On" >> /usr/local/etc/php/php.ini

COPY --chmod=755 ./files/docker-entrypoint.sh /docker-entrypoint.sh

COPY --chown=www-data:www-data --chmod=755 ./html/ /var/www/html/

EXPOSE 80

ENTRYPOINT [ "/docker-entrypoint.sh" ]