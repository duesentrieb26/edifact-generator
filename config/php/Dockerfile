# Use the official PHP image with the version that matches your project requirements
FROM php:8.3-cli

# Install system dependencies (if any)
RUN apt-get update && apt-get install -y \
    git \
    unzip

# Install Composer globally
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && php -r "unlink('composer-setup.php');"



# Copy your application code to the container
# COPY . /app


# Set the working directory inside the container
WORKDIR /app


# Allow Composer to run as root inside the container
ENV COMPOSER_ALLOW_SUPERUSER=1

# Code is mounted at runtime (compose.yaml), so install dependencies on start, then run tests
CMD ["sh", "-c", "composer install --no-interaction && ./vendor/bin/phpunit"]