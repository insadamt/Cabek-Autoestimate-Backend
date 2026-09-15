#!/bin/sh
set -eu

if [ "${APP_ENV:-local}" = "production" ] && [ -z "${APP_KEY:-}" ]; then
    echo >&2 "AutoEstimate refused to start: APP_KEY is empty in production."
    exit 1
fi

exec "$@"
