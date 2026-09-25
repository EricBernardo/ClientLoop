#!/bin/sh

set -eu

mkdir -p storage/app/private/imports storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs

# Evita que uma configuração cacheada faça os testes usarem o PostgreSQL local.
php artisan config:clear

until php artisan migrate --force; do
    echo "Aguardando o PostgreSQL ficar disponível..."
    sleep 2
done

php artisan clientloop:ensure-demo

exec php artisan serve --host=0.0.0.0 --port=8000
