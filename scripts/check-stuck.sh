#!/bin/bash
cd /var/www/html || exit 1
U=office-bms
PHPV=8.4
FAILS=0
as() { su -s /bin/bash $U -c "$*"; }
ok() { echo "[OK]   $*"; }
warn() { echo "[WARN] $*"; }
bad() { echo "[FAIL] $*"; FAILS=$((FAILS+1)); }
RP=$(grep ^REDIS_PASSWORD .env | cut -d= -f2)
R() { redis-cli -a "$RP" --no-auth-warning "$@" 2>/dev/null; }
NOW=$(date +%s)

echo "== REDIS + QUEUE (stuck or expired jobs) =="
if [ "$(R ping)" = PONG ]; then ok "redis answers"; else bad "redis not answering - run scripts/setup-container.sh"; fi
KEYS=$(R --scan --pattern '*queues:*')
if [ -z "$KEYS" ]; then ok "no queue keys (queue empty)"; fi
for k in $KEYS; do
  t=$(R type "$k")
  if [ "$t" = list ]; then
    n=$(R llen "$k")
    if [ "$n" -gt 50 ]; then bad "$k waiting=$n (backlog - are workers running?)"; else ok "$k waiting=$n"; fi
  elif [ "$t" = zset ]; then
    total=$(R zcard "$k")
    due=$(R zcount "$k" -inf "$NOW")
    case "$k" in
      *:reserved)
        if [ "$due" -gt 0 ]; then bad "$k running=$total but $due past their retry deadline (stuck job)"; else ok "$k running=$total, none expired"; fi ;;
      *:delayed)
        if [ "$due" -gt 0 ]; then warn "$k delayed=$total, $due already due (should move within seconds)"; else ok "$k delayed=$total, none overdue"; fi ;;
      *) ok "$k size=$total" ;;
    esac
  fi
done

echo "== WORKERS =="
SUP=$(supervisorctl status 2>&1)
echo "$SUP"
RUN=$(echo "$SUP" | grep -c RUNNING)
if [ "$RUN" -ge 2 ]; then ok "$RUN workers RUNNING"; else bad "only $RUN workers RUNNING (need 2) - service supervisor start; supervisorctl restart all"; fi
if echo "$SUP" | grep -qE 'BACKOFF|FATAL|STOPPED|EXITED'; then bad "a worker is BACKOFF/FATAL/STOPPED - check storage/logs/worker.log"; else ok "no worker in a bad state"; fi
FAILED=$(as 'php artisan queue:failed' | grep -cE '^\s*[0-9a-f-]{8,}')
if [ "$FAILED" = 0 ]; then ok "no failed jobs"; else bad "$FAILED failed jobs - php artisan queue:failed"; fi

echo "== CRON / SCHEDULER =="
if pgrep -x cron >/dev/null; then ok "cron process running"; else bad "cron not running - service cron start"; fi
if crontab -u $U -l 2>/dev/null | grep -q schedule:run; then ok "schedule:run is in the $U crontab"; else bad "schedule:run missing from the $U crontab - run scripts/setup-container.sh"; fi
if [ -f storage/logs/worker.log ]; then
  AGE=$(( (NOW - $(stat -c %Y storage/logs/worker.log)) / 60 ))
  if [ "$AGE" -le 75 ]; then ok "scheduler heartbeat: worker.log written $AGE min ago"; else warn "worker.log last written $AGE min ago (normal right after a restart; should refresh at the next :00)"; fi
else
  warn "storage/logs/worker.log not found yet"
fi
if [ "$1" = probe ]; then
  if as 'php artisan schedule:run' >/dev/null 2>&1; then ok "schedule:run executes without error (probe)"; else bad "schedule:run failed (probe)"; fi
else
  echo "(add the word probe to also run schedule:run once: bash scripts/check-stuck.sh probe)"
fi

echo "== PERMISSIONS =="
OWN=$(find storage bootstrap/cache public/build ! -user $U 2>/dev/null | head -3)
if [ -z "$OWN" ]; then ok "storage, bootstrap/cache, public/build owned by $U"; else bad "not owned by $U: $OWN"; fi
NW=$(as 'find storage bootstrap/cache -type d ! -writable 2>/dev/null | head -2')
if [ -z "$NW" ]; then ok "all storage and cache directories are writable by $U"; else bad "not writable by $U: $NW"; fi
if as 'touch storage/app/public/.t storage/framework/cache/.t storage/framework/sessions/.t storage/logs/.t bootstrap/cache/.t && rm storage/app/public/.t storage/framework/cache/.t storage/framework/sessions/.t storage/logs/.t bootstrap/cache/.t' >/dev/null 2>&1; then ok "$U can write storage, sessions, logs, bootstrap/cache"; else bad "write test failed - chown -R $U:$U storage bootstrap/cache public/build"; fi
if as 'test -r .env' ; then ok ".env readable by $U"; else bad ".env not readable by $U"; fi
if [ -L public/storage ]; then ok "public/storage symlink present"; else bad "public/storage symlink missing - php artisan storage:link"; fi

echo "== PHP-FPM WORKERS =="
MC=$(php-fpm$PHPV -tt 2>&1 | grep -E 'pm\.max_children' | head -1 | grep -oE '[0-9]+$')
if [ "${MC:-0}" -ge 30 ]; then ok "pm.max_children=$MC (tuned value is 30)"; else bad "pm.max_children=${MC:-unknown}, tuned value is 30 - a platform override may be capping workers: bash scripts/fix-workers.sh"; fi

echo "== LIVEWIRE UPLOAD =="
mkdir -p storage/app/livewire-tmp
if as 'touch storage/app/livewire-tmp/.t && rm storage/app/livewire-tmp/.t' >/dev/null 2>&1; then ok "livewire-tmp is writable by $U"; else bad "livewire-tmp not writable - chown -R $U:$U storage"; fi
OLD=$(find storage/app/livewire-tmp -type f -mmin +1440 2>/dev/null | wc -l)
if [ "$OLD" -gt 200 ]; then warn "$OLD temp uploads older than a day are piling up"; else ok "temp uploads not piling up ($OLD older than a day)"; fi
for key in upload_max_filesize post_max_size; do
  v=$(php-fpm$PHPV -i 2>/dev/null | grep -E "^$key " | head -1 | awk '{print $3}')
  if [ "${v%M}" -ge 12 ] 2>/dev/null; then ok "$key=$v (need 12M or more)"; else bad "$key=$v is too low for uploads (need 12M or more)"; fi
done
if as 'php artisan route:list' 2>/dev/null | grep -q 'upload-file'; then ok "livewire upload route is registered"; else bad "livewire upload route missing - php artisan optimize:clear && php artisan optimize"; fi

echo "== RESULT =="
if [ "$FAILS" = 0 ]; then echo "ALL GOOD: nothing stuck"; else echo "$FAILS problem(s) above"; fi
exit $FAILS
