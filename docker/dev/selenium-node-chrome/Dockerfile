
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

ARG SELENIUM_VERSION

FROM selenium/node-chrome:${SELENIUM_VERSION}

ARG CHROME_ARG_PROXY_SERVER

USER root

# overriding launch arguments for chrome executable
COPY wrap_chrome_binary /opt/bin/wrap_chrome_binary-overrides
RUN chmod +x /opt/bin/wrap_chrome_binary-overrides && \
    /opt/bin/wrap_chrome_binary-overrides

USER 1200
