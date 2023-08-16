<?php

global $allowedCIDR, $allowedIPs;
require_once '306d717c3f592af0186ed31e2f056a7d/config.php';
function getClientIP()
{
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        return $_SERVER['REMOTE_ADDR'];
    }
    return '';
}

// Check if the remote IP address is allowed
$remoteIP = getClientIP();

// Function to check if an IP is within a given CIDR range
function ipInRange($ip, $range)
{
    list($subnet, $bits) = explode('/', $range);
    $subnet = ip2long($subnet);
    $ip = ip2long($ip);
    $mask = -1 << (32 - $bits);
    $subnet &= $mask;
    return ($ip & $mask) == $subnet;
}

if (!ipInRange($remoteIP, $allowedCIDR) && !in_array($remoteIP, $allowedIPs)) {
    header('HTTP/1.0 403 Forbidden');
    echo 'Access denied.';
    exit();
}