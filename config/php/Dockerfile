# Use the official PHP image with the version that matches your project requirements
FROM php:7.2-cli

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


# Set the user to run Composer commands
ARG COMPOSER_ALLOW_SUPERUSER=1

# Install project dependencies via Composer
RUN composer install

# Command to run tests
CMD ["./vendor/bin/phpunit", "tests/"]