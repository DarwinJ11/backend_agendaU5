FROM php:8.2-cli

# Instalar extensiones MySQL
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Establecer carpeta de trabajo
WORKDIR /app

# Copiar proyecto
COPY . .

# Exponer puerto
EXPOSE 8080

# Iniciar servidor PHP nativo (SIN Apache)
CMD ["php", "-S", "0.0.0.0:8080", "-t", "."]
