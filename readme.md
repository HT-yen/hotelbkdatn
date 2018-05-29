## guide
cd project
vagrant up (install vagrant before)
vagrant ssh
cd project
composer install
bower install
php artisan key:generate
php artisan migrate --seed
php artisan vendor:publish to publish
php artisan serve to start the app on http://localhost:8000/
