FROM docker.io/library/mariadb:11.4

COPY legacy.sql /docker-entrypoint-initdb.d/01-legacy.sql
COPY 02-modernize.sql /docker-entrypoint-initdb.d/02-modernize.sql

RUN chmod 0644 /docker-entrypoint-initdb.d/*.sql
