FROM php:7.3.4-apache

COPY --chmod=755 ./files/docker-entrypoint.sh /docker-entrypoint.sh

COPY --chown=www-data:www-data --chmod=755 ./html/ /var/www/html/

EXPOSE 80

ENTRYPOINT [ "/docker-entrypoint.sh" ]