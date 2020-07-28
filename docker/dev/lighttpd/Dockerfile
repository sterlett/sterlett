
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

ARG ALPINE_VERSION=3.12

FROM alpine:${ALPINE_VERSION}

ARG LIGHTTPD_VERSION

WORKDIR /var/www/localhost/htdocs

USER root

# system
RUN apk add --update --no-cache \
	lighttpd=${LIGHTTPD_VERSION}

RUN rm -rf /var/cache/apk/*

# configurations
COPY lighttpd.conf /etc/lighttpd/lighttpd-custom.conf

# permissions
RUN chown lighttpd:lighttpd /var/www/localhost/htdocs

USER lighttpd

RUN mkdir /var/www/localhost/htdocs/.lighttpd

CMD ["lighttpd", "-D", "-f", "/etc/lighttpd/lighttpd-custom.conf", "2>&1"]
