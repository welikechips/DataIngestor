<?php
/**
 * Access Control Module for DataIngestor
 *
 * This script handles IP-based access control for the application by
 * checking if the requesting client's IP is within allowed ranges or
 * included in the allowed IPs list.
 *
 * @package DataIngestor
 * @author Original Developer
 * @version 1.0
 */

// Import configuration variables from config file
global $allowedCIDR, $allowedIPs;
require_once '306d717c3f592af0186ed31e2f056a7d/config.php';

/**
 * Gets the client's real IP address considering proxy headers
 *
 * This function attempts to determine the actual client IP address by
 * checking X-Forwarded-For headers (common in proxy setups) before
 * falling back to REMOTE_ADDR.
 *
 * @return string The detected client IP address
 */
function getClientIP()
{
    // Check for X-Forwarded-For header first (proxy scenario)
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]); // Get the first IP which is the client's real IP
    }
    // Fall back to REMOTE_ADDR if no proxy headers exist
    elseif (isset($_SERVER['REMOTE_ADDR'])) {
        return $_SERVER['REMOTE_ADDR'];
    }

    // Return empty string if no IP could be detected
    return '';
}

// Get the client's IP address
$remoteIP = getClientIP();

/**
 * Checks if an IP address is within a given CIDR range
 *
 * @param string $ip IP address to check
 * @param string $range CIDR range notation (e.g., "192.168.1.0/24")
 * @return bool True if IP is in range, false otherwise
 */
function ipInRange($ip, $range)
{
    // Split CIDR notation into network address and prefix length
    list($subnet, $bits) = explode('/', $range);

    // Convert IP addresses to long integer format
    $subnet = ip2long($subnet);
    $ip = ip2long($ip);

    // Calculate the subnet mask based on prefix length
    $mask = -1 << (32 - $bits);

    // Apply mask to subnet
    $subnet &= $mask;

    // Check if IP is in subnet range
    return ($ip & $mask) == $subnet;
}

// Block access if IP is not within allowed CIDR range or not in allowed IPs list
if (!ipInRange($remoteIP, $allowedCIDR) && !in_array($remoteIP, $allowedIPs)) {
    header('HTTP/1.0 403 Forbidden');
    echo 'Access denied.';
    exit();
}