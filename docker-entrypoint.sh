#!/bin/bash
set -e

# Create the SQLite database if it doesn't exist
if [ ! -f /var/www/html/306d717c3f592af0186ed31e2f056a7d/data.db ]; then
    echo "Creating database..."
    cd /var/www/html/306d717c3f592af0186ed31e2f056a7d
    sqlite3 data.db "CREATE TABLE entries (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        data TEXT NOT NULL,
        referrer TEXT,
        client_ip TEXT NOT NULL,
        entry_datetime DATETIME
    );"
    echo "Database created successfully."
fi

# Ensure proper ownership
chown -R www-data:www-data /var/www/html

# Execute CMD
exec "$@"