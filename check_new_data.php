<?php
/**
 * New Data Checker for DataIngestor
 *
 * This script checks if new data has been submitted to the database since
 * the last check. It's primarily used by the AJAX polling mechanism in
 * the web interface to provide real-time updates.
 *
 * @package DataIngestor
 * @author Original Developer
 * @version 1.0
 */

// Import access control module to ensure only authorized clients can access this script
require_once 'check_access.php';

// Connect to the SQLite database
$db = new SQLite3('306d717c3f592af0186ed31e2f056a7d/data.db');

// Get the latest entry ID that the client already knows about
$latestId = $_GET['latestId'];

/**
 * Check for new data entries with IDs higher than the provided latest ID
 *
 * Uses prepared statements to prevent SQL injection and counts how many
 * new entries exist in the database.
 */
$stmt = $db->prepare('SELECT COUNT(*) AS count FROM entries WHERE id > :latestId');
$stmt->bindValue(':latestId', $latestId, SQLITE3_INTEGER);
$result = $stmt->execute();
$row = $result->fetchArray(SQLITE3_ASSOC);

// Determine if new data is available
$newDataAvailable = $row['count'] > 0;

// Return a boolean string indicating whether new data is available
echo $newDataAvailable ? 'true' : 'false';