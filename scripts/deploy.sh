php artisan down

git fetch origin main
git reset --hard origin/main
composer install --optimize-autoloader --no-dev --no-interaction --prefer-dist

php artisan optimize:clear
php artisan migrate --force --no-interaction
php artisan optimize

sudo systemctl reload php8.4-fpm
sudo systemctl reload nginx

npm ci
npm run build
npm prune --omit=dev

php artisan up
