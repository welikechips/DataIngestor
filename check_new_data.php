<?php
require_once 'check_access.php';
$db = new SQLite3('306d717c3f592af0186ed31e2f056a7d/data.db');
$latestId = $_GET['latestId'];

// Use prepared statement with bound parameter to prevent SQL injection
$stmt = $db->prepare('SELECT COUNT(*) AS count FROM entries WHERE id > :latestId');
$stmt->bindValue(':latestId', $latestId, SQLITE3_INTEGER);
$result = $stmt->execute();
$row = $result->fetchArray(SQLITE3_ASSOC);
$newDataAvailable = $row['count'] > 0;

echo $newDataAvailable ? 'true' : 'false';
