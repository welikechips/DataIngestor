<?php
/**
 * Delete All Handler for DataIngestor
 *
 * This script handles AJAX requests to delete all data entries.
 *
 * @package DataIngestor
 * @author Original Developer
 * @version 1.0
 */

// Include access control to verify client authorization
require_once 'check_access.php';

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Connect to the database
    $db = new SQLite3('306d717c3f592af0186ed31e2f056a7d/data.db');

    // Delete all entries from the database
    $result = $db->exec('DELETE FROM entries');

    // Return success or failure
    if ($result !== false) {
        echo 'success';
    } else {
        http_response_code(500);
        echo 'failed';
    }
} else {
    // Invalid request method
    http_response_code(405); // Method Not Allowed
    echo 'Invalid request method';
}