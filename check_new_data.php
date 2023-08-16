<?php
$db = new SQLite3('data.db');
$latestId = $_GET['latestId'];

// Use prepared statement with bound parameter to prevent SQL injection
$stmt = $db->prepare('SELECT COUNT(*) AS count FROM entries WHERE id > :latestId');
$stmt->bindValue(':latestId', $latestId, SQLITE3_INTEGER);
$result = $stmt->execute();
$row = $result->fetchArray(SQLITE3_ASSOC);
$newDataAvailable = $row['count'] > 0;

echo $newDataAvailable ? 'true' : 'false';
?>
