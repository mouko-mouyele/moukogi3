#!/usr/bin/env bash
set -euo pipefail

# Écrire le certificat Aiven (contenu dans l'env var AIVEN_MYSQL_CA)
mkdir -p /tmp/ssl
if [ -n "${AIVEN_MYSQL_CA:-}" ]; then
  echo "$AIVEN_MYSQL_CA" > /tmp/ssl/ca.pem
  export MYSQL_ATTR_SSL_CA=/tmp/ssl/ca.pem
fi

# Installer les dépendances (optimisé pour la production)
composer install --no-dev --optimize-autoloader

# Générer la clé d'application si nécessaire
if [ -z "${APP_KEY:-}" ]; then
  php artisan key:generate --force
fi

# Exécuter les migrations en production
php artisan migrate --force || true

# Lancer le serveur PHP intégré (pour tests rapides). En production, utilisez Docker + php-fpm + nginx.
php -S 0.0.0.0:${PORT:-10000} -t public
