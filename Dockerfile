FROM php:8.1-apache

# Copy application source
COPY . /var/www/html/

# Set permissions for JSON storage files
RUN chown -R www-data:www-data /var/www/html \
    && chmod 666 /var/www/html/messages.json /var/www/html/online.json

EXPOSE 80

CMD ["apache2-foreground"]
