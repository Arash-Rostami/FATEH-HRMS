#!/bin/bash
APP=/var/www/html
U=office-bms
PHPV=8.4
cd $APP
as() { su -s /bin/bash $U -c "$*"; }
setkv() { grep -qE "^#? ?$2 " "$1" && sed -i -E "s|^#? ?$2 .*|$2 $3|" "$1" || echo "$2 $3" >> "$1"; }

for p in redis-server supervisor cron; do dpkg -s $p >/dev/null 2>&1 || { apt-get update -qq; apt-get install -y -qq $p; }; done

RP=$(grep ^REDIS_PASSWORD .env | cut -d= -f2)
setkv /etc/redis/redis.conf requirepass "$RP"
setkv /etc/redis/redis.conf maxmemory 2500mb
setkv /etc/redis/redis.conf maxmemory-policy volatile-lru

POOL=$(ls /etc/php/$PHPV/fpm/pool.d/*.conf | head -1)
cat > "$POOL" << 'EOF'
[www]
user = office-bms
group = office-bms
listen = /run/php/php8.4-fpm.sock
listen.owner = office-bms
listen.group = office-bms
pm = dynamic
pm.max_children = 30
pm.start_servers = 10
pm.min_spare_servers = 8
pm.max_spare_servers = 15
pm.max_requests = 1000
pm.status_path = /fpm-status
request_terminate_timeout = 120
clear_env = no
EOF

cat > /etc/php/$PHPV/fpm/conf.d/99-tuning.ini << 'EOF'
opcache.enable=1
opcache.memory_consumption=512
opcache.interned_strings_buffer=32
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
opcache.jit=off
realpath_cache_size=4M
realpath_cache_ttl=600
upload_max_filesize=250M
post_max_size=250M
memory_limit=512M
max_input_vars=5000
max_input_time=300
max_execution_time=120
EOF
cp /etc/php/$PHPV/fpm/conf.d/99-tuning.ini $APP/chabok-php.ini

cat > /etc/supervisor/conf.d/laravel-worker.conf << 'EOF'
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
numprocs=2
user=office-bms
stopwaitsecs=60
redirect_stderr=true
stdout_logfile=/var/www/html/storage/logs/worker.log
EOF

crontab -l 2>/dev/null | grep -q schedule:run && crontab -l | grep -v schedule:run | crontab -
crontab -u $U -l 2>/dev/null | grep -q schedule:run || (crontab -u $U -l 2>/dev/null; echo "* * * * * cd $APP && php artisan schedule:run >> /dev/null 2>&1") | crontab -u $U -

echo 'ServerName localhost' > /etc/apache2/conf-available/servername.conf
cat > /etc/apache2/conf-available/perf.conf << 'EOF'
<LocationMatch "^/build/assets/.*\.(css|js|svg|ttf)$">
    SetOutputFilter DEFLATE
    Header set Cache-Control "public, max-age=31536000, immutable"
</LocationMatch>
<LocationMatch "^/build/assets/.*\.(woff|woff2|png|jpg|jpeg|gif|ico)$">
    Header set Cache-Control "public, max-age=31536000, immutable"
</LocationMatch>
<LocationMatch "^/(sw\.js|sw\.mjs|registerSW\.js|build/manifest\.webmanifest)$">
    SetOutputFilter DEFLATE
    Header set Cache-Control "no-cache"
</LocationMatch>
<LocationMatch "^/audio/">
    Header set Cache-Control "public, max-age=604800"
</LocationMatch>
<LocationMatch "^/module-features\.json$">
    Header set Cache-Control "public, max-age=300"
    Header set Access-Control-Allow-Origin "*"
</LocationMatch>
AddOutputFilterByType DEFLATE text/html application/json
EOF
a2enmod -q deflate headers expires >/dev/null
a2enconf -q servername perf >/dev/null

sed -i 's/^APP_DEBUG=.*/APP_DEBUG=false/; s/^LOG_LEVEL=.*/LOG_LEVEL=warning/' .env

mkdir -p storage/app/public storage/app/livewire-tmp storage/framework/{cache,sessions,views} storage/logs bootstrap/cache
[ -f public/build/manifest.json ] || { npm ci && npm run build; }
chown -R $U:$U storage bootstrap/cache public/build public/audio 2>/dev/null
find storage bootstrap/cache -type d -exec chmod 775 {} + -o -type f -exec chmod 664 {} +
find storage bootstrap/cache -type d -exec chmod g+s {} +
[ -d public/audio ] && find public/audio -type d -exec chmod 755 {} + -o -type f -exec chmod 644 {} +
[ -L public/storage ] || as 'php artisan storage:link'

service redis-server restart
for i in $(seq 1 10); do redis-cli -a "$(grep ^REDIS_PASSWORD .env | cut -d= -f2)" ping 2>/dev/null | grep -q PONG && break; sleep 1; done

as 'php artisan optimize:clear && php artisan optimize'

pgrep -x supervisord >/dev/null || service supervisor start
pgrep -x cron >/dev/null || service cron start
php-fpm$PHPV -t && service php$PHPV-fpm restart
apache2ctl configtest && service apache2 reload
supervisorctl reread >/dev/null; supervisorctl update >/dev/null; supervisorctl restart all

echo "=== VERIFY ==="
redis-cli -a "$(grep ^REDIS_PASSWORD .env | cut -d= -f2)" config get maxmemory-policy 2>/dev/null | tail -1
supervisorctl status
crontab -u $U -l | grep schedule:run
as 'touch storage/logs/t && rm storage/logs/t' && echo WRITE_OK
curl -s -o /dev/null -w "HTTP %{http_code}\n" http://localhost
