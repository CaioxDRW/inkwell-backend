FROM php:8.2-cli

# Instala extensão PDO e MySQL
RUN docker-php-ext-install pdo pdo_mysql

# Define o diretório de trabalho
WORKDIR /var/www/html

# Copia os arquivos do projeto
COPY . .

# Roda o servidor embutido do PHP ouvindo na porta informada pelo Railway
CMD php -S 0.0.0.0:${PORT:-8080} -t /var/www/html