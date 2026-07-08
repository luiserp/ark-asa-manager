FROM ubuntu:24.04

LABEL maintainer="ark-asa-server"

ARG WWWGROUP=1000
ARG NODE_VERSION=24

WORKDIR /var/www/html

ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=UTC
ENV LANG=C.UTF-8

RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

RUN apt-get update && apt-get upgrade -y \
    && mkdir -p /etc/apt/keyrings \
    && apt-get install -y gnupg gosu curl ca-certificates zip unzip git sqlite3 libcap2-bin libpng-dev dnsutils openssh-client \
    && curl -sS 'https://keyserver.ubuntu.com/pks/lookup?op=get&search=0xb8dc7e53946656efbce4c1dd71daeaab4ad4cab6' | gpg --dearmor | tee /etc/apt/keyrings/ppa_ondrej_php.gpg > /dev/null \
    && echo "deb [signed-by=/etc/apt/keyrings/ppa_ondrej_php.gpg] https://ppa.launchpadcontent.net/ondrej/php/ubuntu noble main" > /etc/apt/sources.list.d/ppa_ondrej_php.list \
    && apt-get update \
    && apt-get install -y \
        php8.5-cli \
        php8.5-dev \
        php8.5-sqlite3 \
        php8.5-gd \
        php8.5-curl \
        php8.5-mbstring \
        php8.5-xml \
        php8.5-zip \
        php8.5-bcmath \
        php8.5-intl \
        php8.5-readline \
        php8.5-redis \
        php8.5-swoole \
        php8.5-pcov \
        php8.5-pgsql \
        php8.5-mysql \
    && curl -sLS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer \
    && curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg \
    && echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_$NODE_VERSION.x nodistro main" > /etc/apt/sources.list.d/nodesource.list \
    && apt-get update \
    && apt-get install -y nodejs \
    && npm install -g npm \
    && apt-get -y autoremove \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN setcap "cap_net_bind_service=+ep" /usr/bin/php8.5

RUN groupadd --force -g $WWWGROUP app
RUN useradd -ms /bin/bash --no-user-group -g $WWWGROUP -u 1337 app

RUN mkdir -p /home/app/.ssh \
    && chmod 700 /home/app/.ssh \
    && chown -R app:app /home/app/.ssh

COPY . /var/www/html

RUN composer install --no-interaction \
    && npm ci \
    && npm run build \
    && rm -rf node_modules \
    && npm install chokidar

RUN chown -R app:app /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database

COPY docker/app/php.ini /etc/php/8.5/cli/conf.d/99-app.ini
COPY docker/app/start-container /usr/local/bin/start-container
RUN chmod +x /usr/local/bin/start-container

EXPOSE 8000
EXPOSE 8080

ENTRYPOINT ["start-container"]
