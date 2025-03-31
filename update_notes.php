<?php
/**
 * Notes Update Handler for DataIngestor
 *
 * This script handles AJAX requests to update notes for data entries.
 *
 * @package DataIngestor
 * @author Original Developer
 * @version 1.0
 */

// Include access control to verify client authorization
require_once 'check_access.php';

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && isset($_POST['notes'])) {
    $id = $_POST['id'];
    $notes = $_POST['notes'];

    // Connect to the database
    $db = new SQLite3('306d717c3f592af0186ed31e2f056a7d/data.db');

    // Update the notes field for the specified entry
    $stmt = $db->prepare('UPDATE entries SET notes = :notes WHERE id = :id');
    $stmt->bindValue(':notes', $notes, SQLITE3_TEXT);
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $result = $stmt->execute();

    // Return success or failure
    if ($result) {
        echo 'success';
    } else {
        http_response_code(500);
        echo 'failed';
    }
} else {
    // Invalid request
    http_response_code(400);
    echo 'invalid request';
}