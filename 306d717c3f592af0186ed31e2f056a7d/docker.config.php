<?php
/**
 * Docker Configuration for DataIngestor
 *
 * Default configuration file optimized for Docker deployment.
 * Rename to config.php in the 306d717c3f592af0186ed31e2f056a7d directory.
 *
 * @package DataIngestor
 * @version 1.0
 */

/**
 * Allowed CIDR Range
 *
 * By default, this allows all IP addresses when running in Docker.
 * You should restrict this in production environments.
 *
 * @var string
 */
$allowedCIDR = '0.0.0.0/0';

/**
 * Allowed Individual IP Addresses
 *
 * Docker-specific IPs that should be allowed to access the application.
 * - 127.0.0.1 - Localhost
 * - 172.17.0.1 - Default Docker bridge network gateway
 *
 * Add your specific IPs for production use.
 *
 * @var array
 */
$allowedIPs = [
    '127.0.0.1',     // Localhost
    '172.17.0.1',    // Default Docker bridge network gateway
    '172.18.0.1',    // Alternative Docker network
    // Add your IP addresses below
];