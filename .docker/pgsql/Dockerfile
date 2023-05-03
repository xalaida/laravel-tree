FROM postgres:14.2-alpine

ENV POSTGRES_DB=app
ENV POSTGRES_USER=app
ENV POSTGRES_PASSWORD=app

HEALTHCHECK --interval=3s --timeout=5s --retries=5 \
  CMD pg_isready -U app -d app
