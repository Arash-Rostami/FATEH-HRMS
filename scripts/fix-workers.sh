#!/bin/bash
PHPV=8.4
DIR=/etc/php/$PHPV/fpm/pool.d
BAK=/root/pool-overrides
MOVED=0
mkdir -p $BAK
for f in $DIR/*.conf; do
  [ "$(basename "$f")" = www.conf ] && continue
  if grep -qE '^\[www\]' "$f" && grep -qE '^[[:space:]]*pm\.' "$f"; then
    mv "$f" "$BAK/$(basename "$f").$(date +%s).bak"
    echo "moved platform pool override: $f"
    MOVED=$((MOVED+1))
  fi
done
[ "$MOVED" = 0 ] && echo "no platform pool override found in $DIR"
if php-fpm$PHPV -t >/dev/null 2>&1; then
  service php$PHPV-fpm reload >/dev/null 2>&1
  echo "php-fpm reloaded"
else
  echo "[FAIL] php-fpm config test failed - restore the file(s) from $BAK"
  exit 1
fi
php-fpm$PHPV -tt 2>&1 | grep -E 'pm\.(max_children|start_servers|min_spare_servers|max_spare_servers|max_requests)' | sed 's/.*NOTICE: *//'
