FROM php:8.2-apache

# Instalar librerías del sistema y soporte oficial de PostgreSQL (Supabase) y MySQL
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    unzip \
    && docker-php-ext-install -j$(nproc) pdo pdo_pgsql pdo_mysql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Habilitar módulos esenciales de Apache
RUN a2enmod rewrite headers

# Directorio de trabajo
WORKDIR /var/www/html

# Copiar archivos del proyecto al contenedor
COPY . /var/www/html/

# Asegurar permisos de ejecución y escritura
RUN chown -R www-data:www-data /var/www/html

# Script de inicio para adaptar automáticamente el puerto dinámico de Render ($PORT)
RUN echo '#!/bin/bash\n\
PORT="${PORT:-80}"\n\
sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf\n\
sed -i "s/<VirtualHost \*:80>/<VirtualHost \*:${PORT}>/g" /etc/apache2/sites-available/*.conf\n\
exec apache2-foreground' > /usr/local/bin/docker-entrypoint.sh \
    && chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80 10000

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
