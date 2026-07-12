FROM docker.io/library/php:8.4-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        git \
        unzip \
        libicu-dev \
        libzip-dev \
    && docker-php-ext-install pdo_mysql intl zip \
    && a2enmod rewrite \
    && curl -fsSL https://getcomposer.org/installer -o /tmp/composer-setup.php \
    && php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && rm -f /tmp/composer-setup.php \
    && rm -rf /var/lib/apt/lists/*

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf \
    /etc/apache2/conf-available/*.conf \
    && printf '%s\n' \
        '<Directory /var/www/html/public>' \
        '    AllowOverride All' \
        '    Require all granted' \
        '</Directory>' \
        > /etc/apache2/conf-available/fotbaltesty.conf \
    && a2enconf fotbaltesty \
    && printf '%s\n' 'ServerName localhost' > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername

WORKDIR /var/www/html

COPY composer.json ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader

COPY app ./app
COPY config ./config
COPY public ./public
COPY scripts ./scripts
COPY docker/web-entrypoint.sh /usr/local/bin/fotbaltesty-entrypoint

RUN mkdir -p temp/cache log \
    && chown -R www-data:www-data temp log \
    && chmod +x /usr/local/bin/fotbaltesty-entrypoint

ENTRYPOINT ["/usr/local/bin/fotbaltesty-entrypoint"]
CMD ["apache2-foreground"]
