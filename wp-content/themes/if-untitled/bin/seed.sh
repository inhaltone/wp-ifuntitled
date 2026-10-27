#!/usr/bin/env sh
# Content seed (implementation plan §3). Run from the theme folder: bin/seed.sh [seeds-dir]
set -e
cd "$(dirname "$0")/.."
exec wp eval-file bin/seed.php "$@"
