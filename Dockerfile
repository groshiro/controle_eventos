# Usa a imagem oficial do PHP com Apache
FROM php:8.2-apache

# Instala as dependências necessárias para o PostgreSQL
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Copia os arquivos do seu projeto para dentro do servidor Apache
COPY . /var/www/html/

# Dá permissões para o Apache ler os arquivos
RUN chown -R www-data:www-data /var/www/html/

# Expõe a porta 80 (padrão do HTTP)
EXPOSE 80