FROM php:8.1-apache

# Copy application source
COPY . /var/www/html/

# Ensure JSON data files are writable by the web server
RUN chown -R www-data:www-data /var/www/html \
    && chmod 664 /var/www/html/messages.json /var/www/html/online.json

# Run Apache as the www-data user for better security and to avoid
# permission issues when writing to the JSON files
USER www-data

EXPOSE 80

CMD ["apache2-foreground"]
