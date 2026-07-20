FROM php:8.2-apache

# Instala extensão PDO e MySQL para o PHP conseguir falar com o banco
RUN docker-php-ext-install pdo pdo_mysql

# Copia todos os seus arquivos PHP para a pasta do servidor Apache
COPY . /var/www/html/

# Ativa o módulo de reescrita do Apache se precisar no futuro
RUN a2enmod rewrite

EXPOSE 80