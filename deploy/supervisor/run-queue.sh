#!/usr/bin/env bash

CONTAINER="apache-php85"
WORK_DIR="/var/www/html/personal-v4.novaardiansyah.id"
ARTISAN="$WORK_DIR/artisan"

cleanup() {
  docker exec "$CONTAINER" pkill -TERM -f "$ARTISAN queue:work" 2>/dev/null || true
  exit 0
}

trap cleanup SIGTERM SIGINT SIGHUP

docker exec "$CONTAINER" pkill -9 -f "$ARTISAN queue:work" 2>/dev/null || true

docker exec -i -w "$WORK_DIR" "$CONTAINER" php "$ARTISAN" queue:work --sleep=3 --tries=3 &
PID=$!
wait $PID
