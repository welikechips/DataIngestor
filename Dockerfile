FROM php:7.4-apache

# Install SQLite and other dependencies
RUN apt-get update && apt-get install -y \
    sqlite3 \
    libsqlite3-dev \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache modules
RUN a2enmod rewrite headers

# Configure Apache virtual host
COPY 000-default.conf /etc/apache2/sites-available/000-default.conf

# Set up the application
WORKDIR /var/www/html
COPY . /var/www/html/

# Create the data directory if it doesn't exist
RUN mkdir -p 306d717c3f592af0186ed31e2f056a7d

# Create a sample config.php if it doesn't exist (will be overridden by volume)
RUN echo '<?php\n$allowedCIDR = "0.0.0.0/0";\n$allowedIPs = ["127.0.0.1", "172.17.0.1"];\n?>' > 306d717c3f592af0186ed31e2f056a7d/config.php

# Set permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

# Remove default index.html if it exists
RUN rm -f /var/www/html/index.html

# Set up SQLite database in entrypoint
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Use custom entrypoint
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]

# Expose port 80
EXPOSE 80