# Magento runtime for the end-to-end tests (PHP built-in server, development only).
# Debian bookworm ships ICU 72; with ICU 76 (trixie) Magento 2.4.7 calendars break for pt_BR, de_DE, fr_FR...
FROM php:8.2-cli-bookworm

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions bcmath gd intl pdo_mysql soap sockets xsl zip ftp opcache @composer \
    && apt-get update \
    && apt-get install -y --no-install-recommends git unzip procps \
    && rm -rf /var/lib/apt/lists/* \
    && { echo "memory_limit=4G"; echo "max_execution_time=1800"; echo "opcache.enable_cli=0"; } \
        > /usr/local/etc/php/conf.d/zz-magento.ini

COPY setup.sh /usr/local/bin/esr-setup
RUN chmod +x /usr/local/bin/esr-setup

WORKDIR /var/www/magento
CMD ["esr-setup"]
