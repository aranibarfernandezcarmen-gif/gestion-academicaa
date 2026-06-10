# Imagen para desplegar Gestión Académica (Laravel 12 + Inertia/Vue) en Render.
# El build del frontend (Vite) importa Ziggy desde /vendor, por eso composer install
# debe ejecutarse ANTES de "npm run build". Por eso usamos una sola etapa.
FROM php:8.2-cli

# Dependencias del sistema + extensiones PHP + Node.js 20
# (gd se necesita para dompdf y phpspreadsheet)
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip ca-certificates curl gnupg \
        libpq-dev libonig-dev libzip-dev \
        libpng-dev libjpeg-dev libfreetype-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql mbstring bcmath zip gd \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copiar el código (vendor/ y node_modules/ se ignoran vía .dockerignore)
COPY . .

# Dependencias PHP (vendor es necesario para el import de Ziggy en el build de Vite)
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Build del frontend Vue -> genera public/build, luego limpiamos node_modules
RUN npm ci && npm run build && rm -rf node_modules

# Permisos de escritura para Laravel
RUN chmod -R 775 storage bootstrap/cache

# Script de arranque (migraciones + servidor)
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Hace que "php artisan serve" use varios procesos -> evita 404 intermitentes
ENV PHP_CLI_SERVER_WORKERS=4

EXPOSE 8080
CMD ["start.sh"]
