FROM php:8.1-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends unzip git \
    && rm -rf /var/lib/apt/lists/* \
    && git config --global --add safe.directory /repo

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
