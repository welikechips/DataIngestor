# DataIngestor

Create the database 
```console
sqlite3 data.db "CREATE TABLE entries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    data TEXT NOT NULL,
    referrer TEXT,
    client_ip TEXT NOT NULL,
    entry_datetime DATETIME
);"
```

Add your ip to the $allowedIPs variable in the index.php file

Run the php server
```console
php -S 0.0.0.0:443 ./
```
