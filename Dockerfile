FROM php:8.2-apache

# Instala as dependências e as extensões MySQLi e PDO automaticamente
RUN apt-get update && apt-get install -y \
    libmariadb-dev \
    && docker-php-ext-install mysqli pdo pdo_mysql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Ativa o módulo de reescrita do Apache para o .htaccess
RUN a2enmod rewrite

# Copia a configuração personalizada do PHP para o servidor
COPY custom-php.ini /usr/local/etc/php/conf.d/

# Copia os ficheiros do projeto para o servidor
COPY . /var/www/html/

# Cria a pasta oficial e atribui permissões totais de escrita (Chmod 777)
RUN mkdir -p /var/www/html/upload && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 777 /var/www/html/upload
EXPOSE 80