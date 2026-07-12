#!/bin/sh
set -eu

podman-compose down 2>/dev/null || true
podman-compose build --no-cache
podman-compose up
