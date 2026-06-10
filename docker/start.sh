#!/usr/bin/env sh
# Arranque del contenedor en Render.
set -e

# Descubrir paquetes y limpiar cachés que pudieran haber quedado en la imagen
php artisan package:discover --ansi || true
php artisan config:clear || true
php artisan cache:clear || true

# Crear/actualizar tablas (incluye sessions, cache y jobs)
php artisan migrate --force

# Cargar datos de prueba SOLO si RUN_SEED=true (ponlo en true en el primer deploy
# y luego cámbialo a false para no duplicar datos en cada reinicio).
if [ "$RUN_SEED" = "true" ]; then
  echo ">> Ejecutando seeders (RUN_SEED=true)"
  php artisan db:seed --force || true
  echo ">> Resincronizando secuencias de PostgreSQL"
  php artisan db:seed --class=FixSequencesSeeder --force || true
fi

# Servir la app en el puerto que asigna Render
php artisan serve --host 0.0.0.0 --port "${PORT:-8080}"
