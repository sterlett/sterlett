
# Copyright (c) 2020 Pavel Petrov <itnelo@gmail.com>
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses>.

ARG PHP_VERSION

FROM php:${PHP_VERSION}-cli

ARG TIMEZONE
ARG DEPLOYMENT_PATH
ARG APP_SERVER_PORT_EXPOSE

WORKDIR ${DEPLOYMENT_PATH}

USER root

# timezone
RUN ln -snf /usr/share/zoneinfo/${TIMEZONE} /etc/localtime && echo ${TIMEZONE} > /etc/timezone && \
    printf '[PHP]\ndate.timezone = "%s"\n', ${TIMEZONE} > /usr/local/etc/php/conf.d/tzone.ini && \
    date

# xdebug
RUN pecl install xdebug-beta && docker-php-ext-enable xdebug
COPY docker-php-ext-xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# pcntl
RUN docker-php-ext-install pcntl

COPY php.ini /usr/local/etc/php/conf.d/php.ini

# composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    composer --version

USER www-data

CMD ["bin/app"]
