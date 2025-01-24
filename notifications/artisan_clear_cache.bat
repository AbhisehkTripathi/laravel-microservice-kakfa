@echo off
echo Clearing Laravel configuration cache...
php artisan config:clear

echo Clearing Laravel route cache...
php artisan route:clear

echo Clearing Laravel application cache...
php artisan cache:clear

echo Regenerating Composer autoload files...
composer dump-autoload

echo All commands executed successfully!
pause
