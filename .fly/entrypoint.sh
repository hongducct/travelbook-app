#!/usr/bin/env sh

# Run user scripts, if they exist
for f in /var/www/html/.fly/scripts/*.sh; do
    # Bail out this loop if any script exits with non-zero status code
    bash "$f" || break
done
chown -R www-data:www-data /var/www/html

if [ $# -gt 0 ]; then
    # If we passed a command, run it as root
    exec "$@"
else
    # Default to running supervisord with the configuration file
    exec supervisord -c /etc/supervisor/conf.d/supervisord.conf
fi