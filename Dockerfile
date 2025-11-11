# Étape 1: Build des dépendances PHP
FROM composer:2.6 AS composer-build

WORKDIR /app

# Copier les fichiers de dépendances
COPY composer.json composer.lock ./

# Installer les dépendances PHP
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-scripts

# Étape 2: Image finale pour l'application
FROM php:8.3-fpm-alpine

# Installer les extensions PHP nécessaires
RUN apk add --no-cache \
    postgresql-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    curl-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        gd \
        zip \
        curl \
        bcmath

# Créer un utilisateur non-root
RUN addgroup -g 1000 laravel && adduser -G laravel -g laravel -s /bin/sh -D laravel

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier les dépendances installées depuis l'étape de build
COPY --from=composer-build /app/vendor ./vendor

# Copier le reste du code de l'application
COPY --chown=laravel:laravel . .

# Créer les répertoires nécessaires et définir les permissions
RUN mkdir -p storage/framework/{cache,data,sessions,testing,views} \
    && mkdir -p storage/logs \
    && mkdir -p storage/app/public \
    && mkdir -p bootstrap/cache \
    && chown -R laravel:laravel /var/www/html \
    && chmod -R 775 storage bootstrap/cache \
    && chmod -R 775 storage/app

# Créer le script d'entrée directement dans le conteneur
RUN echo '#!/bin/sh' > /usr/local/bin/docker-entrypoint.sh && \
    echo 'set -e' >> /usr/local/bin/docker-entrypoint.sh && \
    echo 'echo "🚀 Démarrage de l'\''application Laravel..."' >> /usr/local/bin/docker-entrypoint.sh && \
    echo 'php artisan config:clear' >> /usr/local/bin/docker-entrypoint.sh && \
    echo 'php artisan cache:clear' >> /usr/local/bin/docker-entrypoint.sh && \
    echo 'php artisan route:clear' >> /usr/local/bin/docker-entrypoint.sh && \
    echo 'php artisan view:clear' >> /usr/local/bin/docker-entrypoint.sh && \
    echo 'echo "⏳ Vérification de la connexion à la base de données..."' >> /usr/local/bin/docker-entrypoint.sh && \
    echo 'MAX_TRIES=30' >> /usr/local/bin/docker-entrypoint.sh && \
    echo 'COUNT=0' >> /usr/local/bin/docker-entrypoint.sh && \
    echo 'until php artisan db:show > /dev/null 2>&1 || [ $COUNT -eq $MAX_TRIES ]; do' >> /usr/local/bin/docker-entrypoint.sh && \
    echo '    echo "Base de données non disponible - tentative $COUNT/$MAX_TRIES"' >> /usr/local/bin/docker-entrypoint.sh && \
    echo '    COUNT=$((COUNT + 1))' >> /usr/local/bin/docker-entrypoint.sh && \
    echo '    sleep 2' >> /usr/local/bin/docker-entrypoint.sh && \
    echo 'done' >> /usr/local/bin/docker-entrypoint.sh && \
    echo 'if [ $COUNT -eq $MAX_TRIES ]; then' >> /usr/local/bin/docker-entrypoint.sh && \
    echo '    echo "❌ Impossible de se connecter à la base de données après $MAX_TRIES tentatives"' >> /usr/local/bin/docker-entrypoint.sh && \
    echo '    echo "Vérifiez vos variables d'\''environnement DB_*"' >> /usr/local/bin/docker-entrypoint.sh && \
    echo 'fi' >> /usr/local/bin/docker-entrypoint.sh && \
    echo 'if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:" ]; then' >> /usr/local/bin/docker-entrypoint.sh && \
    echo '    echo "🔑 Génération de la clé d'\''application..."' >> /usr/local/bin/docker-entrypoint.sh && \
    echo '    php artisan key:generate --force' >> /usr/local/bin/docker-entrypoint.sh && \
    echo 'fi' >> /usr/local/bin/docker-entrypoint.sh && \
    echo 'echo "📊 Exécution des migrations..."' >> /usr/local/bin/docker-entrypoint.sh && \
    echo 'php artisan migrate --force || echo "⚠️ Erreur lors des migrations"' >> /usr/local/bin/docker-entrypoint.sh && \
    echo 'echo "🔐 Installation de Passport..."' >> /usr/local/bin/docker-entrypoint.sh && \
    echo 'if [ ! -f "storage/oauth-private.key" ] || [ ! -f "storage/oauth-public.key" ]; then' >> /usr/local/bin/docker-entrypoint.sh && \
    echo '    echo "Génération des clés Passport..."' >> /usr/local/bin/docker-entrypoint.sh && \
    echo '    php artisan passport:keys --force' >> /usr/local/bin/docker-entrypoint.sh && \
    echo 'fi' >> /usr/local/bin/docker-entrypoint.sh && \
    echo 'php artisan passport:client --personal --no-interaction --name="Personal Access Client" || echo "Client personnel déjà existant"' >> /usr/local/bin/docker-entrypoint.sh && \
    echo 'php artisan passport:client --password --no-interaction --name="Password Grant Client" || echo "Client password déjà existant"' >> /usr/local/bin/docker-entrypoint.sh && \
    echo 'if [ "$APP_ENV" = "production" ]; then' >> /usr/local/bin/docker-entrypoint.sh && \
    echo '    echo "⚡ Optimisation pour la production..."' >> /usr/local/bin/docker-entrypoint.sh && \
    echo '    php artisan config:cache' >> /usr/local/bin/docker-entrypoint.sh && \
    echo '    php artisan route:cache' >> /usr/local/bin/docker-entrypoint.sh && \
    echo '    php artisan view:cache' >> /usr/local/bin/docker-entrypoint.sh && \
    echo 'else' >> /usr/local/bin/docker-entrypoint.sh && \
    echo '    echo "🧹 Nettoyage des caches de production..."' >> /usr/local/bin/docker-entrypoint.sh && \
    echo '    php artisan config:clear' >> /usr/local/bin/docker-entrypoint.sh && \
    echo '    php artisan route:clear' >> /usr/local/bin/docker-entrypoint.sh && \
    echo '    php artisan view:clear' >> /usr/local/bin/docker-entrypoint.sh && \
    echo 'fi' >> /usr/local/bin/docker-entrypoint.sh && \
    echo 'echo "✅ Application prête!"' >> /usr/local/bin/docker-entrypoint.sh && \
    echo 'echo "📍 URL: $APP_URL"' >> /usr/local/bin/docker-entrypoint.sh && \
    echo 'echo "🌍 Environnement: $APP_ENV"' >> /usr/local/bin/docker-entrypoint.sh && \
    echo 'echo "🔌 Port: ${PORT:-8000}"' >> /usr/local/bin/docker-entrypoint.sh && \
    echo 'exec php artisan serve --host=0.0.0.0 --port=${PORT:-8000}' >> /usr/local/bin/docker-entrypoint.sh

# Rendre le script exécutable
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Passer à l'utilisateur non-root
USER laravel

# Exposer le port
EXPOSE 8000

# Point d'entrée
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]