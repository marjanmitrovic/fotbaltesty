#!/bin/sh
set -u

echo '=== Podman ==='
podman --version || exit 1

echo '=== Compose ==='
podman-compose --version || exit 1

echo '=== Containers ==='
podman ps -a || true

echo '=== Images ==='
podman images | grep -E 'fotbaltesty|mariadb|php' || true

echo '=== Networks ==='
podman network ls || true

echo '=== DB logs ==='
podman logs --tail 80 fotbaltesty-modern-v2_db_1 2>/dev/null \
  || podman logs --tail 80 fotbaltesty-modern-phase1_db_1 2>/dev/null \
  || true

echo '=== Web logs ==='
podman logs --tail 80 fotbaltesty-modern-v2_web_1 2>/dev/null \
  || podman logs --tail 80 fotbaltesty-modern-phase1_web_1 2>/dev/null \
  || true
