
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

ARG SELENIUM_NODE_CHROME_IMAGE_DIGEST

FROM selenium/node-chrome@${SELENIUM_NODE_CHROME_IMAGE_DIGEST}

ARG HOST_UID
ARG CHROME_ARG_LOG_LEVEL
ARG CHROME_ARG_PROXY_SERVER

USER root

# reverse-engineered chrome driver (some obvious footprints for webdriver detection are removed)
COPY --chown=seluser:seluser chromedriver-87.0.4280.20-re /opt/selenium/chromedriver-87.0.4280.20-re
RUN mv /opt/selenium/chromedriver-87.0.4280.20 /opt/selenium/chromedriver-87.0.4280.20-base && \
    ln -s /opt/selenium/chromedriver-87.0.4280.20-re /opt/selenium/chromedriver-87.0.4280.20

# overriding launch arguments for chrome executable
COPY wrap_chrome_binary /opt/bin/wrap_chrome_binary-overrides
RUN chmod +x /opt/bin/wrap_chrome_binary-overrides
# as a separate layer, to prevent potentially possible ETXTBSY errors.
RUN /opt/bin/wrap_chrome_binary-overrides

RUN usermod -u ${HOST_UID} seluser

# maintaining compatibility with the original image:
# https://github.com/SeleniumHQ/docker-selenium/blob/selenium-3/Base/Dockerfile#L46
USER seluser:1201
