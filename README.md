# DataIngestor

Create the database 
```console
cd 306d717c3f592af0186ed31e2f056a7d
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

cd 306d717c3f592af0186ed31e2f056a7d
<?php

// Define the allowed CIDR range and IP addresses
$allowedCIDR = '19.12.0.0/16';
$allowedIPs = ['127.0.0.1']; //Add your ip to this array

?>
```

Run the php server
```console
php -S 0.0.0.0:6745 -t ./
```

Make the proxy conf on the remote server under /etc/nginx/conf.d/proxy.conf from proxy.conf.
if you want to work locally and forward your traffic through to another device

```console
ssh root@<remote-ip> -R 6745:127.0.0.1:6745
```