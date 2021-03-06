FROM kaz29/php-apache:7.3.10

ARG HTPASSWD

ENV HTPASSWD "$HTPASSWD"
ENV APACHE_CONFDIR /etc/apache2
ENV APACHE_ENVVARS $APACHE_CONFDIR/envvars

COPY config/000-default.conf /etc/apache2/sites-available/
COPY config/opcache.ini /usr/local/etc/php/conf.d/
COPY config/apc.ini /usr/local/etc/php/conf.d/
COPY config/php.ini.development /usr/local/etc/php/php.ini
COPY config/docker-entrypoint.sh /usr/local/bin/

RUN set -ex \
    && echo "export LISTEN_PORT=80" >> "$APACHE_ENVVARS" \
    && ln -sfT /dev/stderr "$APACHE_LOG_DIR/app-error.log" \
    && ln -sfT /dev/stdout "$APACHE_LOG_DIR/app-access.log" \
    && a2ensite 000-default \
    && echo "$HTPASSWD" > /srv/.htpasswd

COPY cms/composer.json cms/composer.lock /srv/cms/

RUN composer install --working-dir=/srv/cms --no-dev
COPY cms /srv/cms
COPY config/.htaccess /srv/cms/webroot/

RUN echo export BUILD_TIME=`date "+%Y/%m/%d %H:%M:%S"` > /srv/cms/config/.env

RUN set -ex \
    && mkdir -p "/srv/cms/tmp" \
    && chown -R www-data "/srv/cms/tmp"

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

WORKDIR /srv/cms
CMD ["apache2-foreground"]
