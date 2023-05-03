FROM mysql:5.7

ENV MYSQL_ALLOW_EMPTY_PASSWORD=true
ENV MYSQL_USER=app
ENV MYSQL_PASSWORD=app
ENV MYSQL_DATABASE=app

HEALTHCHECK --interval=5s --timeout=5s --retries=10 \
  CMD mysqladmin ping -h localhost
