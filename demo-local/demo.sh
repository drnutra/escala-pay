#!/bin/sh
# Atalho do docker compose da demo local. Uso: ./demo-local/demo.sh up -d --build | ps | logs | down
# DEV=1 liga o modo edição ao vivo (compose.dev.yml). DEMO_PROJECT troca o nome do projeto Docker.
R="$(cd "$(dirname "$0")/.." && pwd)"
D="$R/demo-local"
DEV_FILE=""
[ "${DEV:-0}" = "1" ] && DEV_FILE="-f $D/compose.dev.yml"
exec docker compose -p "${DEMO_PROJECT:-getfy_demo}" -f "$R/docker-compose.yml" -f "$D/compose.override.yml" $DEV_FILE --env-file "$D/demo.env" "$@"
