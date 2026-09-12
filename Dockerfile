FROM php:8.2-apache

# Instala as dependências e as extensões MySQLi e PDO automaticamente
RUN apt-get update && apt-get install -y \
    libmariadb-dev \
    && docker-php-ext-install mysqli pdo pdo_mysql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Ativa o módulo de reescrita do Apache para o .htaccess
RUN a2enmod rewrite

# Copia os ficheiros do projeto para o servidor
COPY . /var/www/html/

# Ajusta as permissões para o Apache
RUN chown -R www-data:www-data /var/www/html/

EXPOSE 80
