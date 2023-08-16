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

create a config.php file with:

```php
<?php

// Define the allowed CIDR range and IP addresses
$allowedCIDR = '19.12.0.0/16';
$allowedIPs = ['127.0.0.1']; //Add your ip to this array

?>
```

Run the php server
```console
php -S 0.0.0.0:443 -t ./
```

if you want to work locally and forward your traffic through to another device

```console
ssh root@<remote-ip> -R 443:127.0.0.1:443
```