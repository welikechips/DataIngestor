<?php

// Include the configuration file
global $allowedCIDR, $allowedIPs;
require_once 'config.php';
$remoteIP = $_SERVER['REMOTE_ADDR'];

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
    exit;
}
// SQLite database setup and connection
$db = new SQLite3('data.db');

// Check if the "delete" parameter is set and delete the corresponding record
if (isset($_POST['delete'])) {
    $deleteId = $_POST['delete'];
    $stmt = $db->prepare('DELETE FROM entries WHERE id = :id');
    $stmt->bindValue(':id', $deleteId, SQLITE3_INTEGER);
    $stmt->execute();
    header('Location: index.php');
    exit();
}

// Check if data is set in the POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['data'])) {
    $data = $_POST['data'];
    $referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
    $clientIP = $_SERVER['REMOTE_ADDR']; // Get the IP address of the client

    // Insert data into the database
    $stmt = $db->prepare('INSERT INTO entries (data, referrer, client_ip, entry_datetime) VALUES (:data, :referrer, :client_ip, :entry_datetime)');
    $stmt->bindValue(':data', $data, SQLITE3_TEXT);
    $stmt->bindValue(':referrer', $referrer, SQLITE3_TEXT);
    $stmt->bindValue(':client_ip', $clientIP, SQLITE3_TEXT);
    $stmt->bindValue(':entry_datetime', date('Y-m-d H:i:s'), SQLITE3_TEXT); // Add current datetime
    $stmt->execute();
    header('Location: index.php');
    exit();
}

// Retrieve data from the database
$query = $db->query('SELECT * FROM entries ORDER BY id DESC');
$dataArray = [];
while ($row = $query->fetchArray(SQLITE3_ASSOC)) {
    $dataArray[] = $row;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Data Ingestor</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</head>
<body>
<div class="container mt-4">
    <h1>Data Ingestor</h1>

    <form method="post">
        <div class="form-group">
            <label for="data">Data:</label>
            <input type="text" class="form-control" name="data" id="data">
        </div>
        <button type="submit" class="btn btn-primary">Submit</button>
    </form>

    <h2 class="mt-4">Saved Data:</h2>
    <div class="table-responsive">
        <table class="table">
            <thead>
            <tr>
                <th>ID</th>
                <th style="width: 300px;">Data</th>
                <th>Referrer</th>
                <th>Client IP</th>
                <th>Date and Time</th> <!-- New column -->
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($dataArray as $dataItem) : ?>
                <tr>
                    <td><?php echo $dataItem['id']; ?></td>
                    <td style="width: 300px;">
                        <div id="data_<?php echo $dataItem['id']; ?>">
                            <?php echo htmlentities(substr($dataItem['data'], 0, 50)); ?>
                        </div>
                        <div id="fullData_<?php echo $dataItem['id']; ?>" style="display: none;">
                            <?php echo htmlentities($dataItem['data']); ?>
                        </div>
                        <?php if (strlen($dataItem['data']) > 50) : ?>
                            <button class="btn btn-link btn-sm" onclick="toggleData(<?php echo $dataItem['id']; ?>)">
                                <span id="toggleText_<?php echo $dataItem['id']; ?>">Show All</span></button>
                        <?php endif; ?>
                        <button class="btn btn-secondary btn-sm ml-2"
                                onclick="copyToClipboard(<?php echo $dataItem['id']; ?>)">Copy to Clipboard
                        </button>
                    </td>
                    <td><?php echo htmlentities($dataItem['referrer']); ?></td>
                    <td><?php echo htmlentities($dataItem['client_ip']); ?></td>
                    <td><?php echo htmlentities($dataItem['entry_datetime']); ?></td> <!-- New column -->
                    <td>
                        <form method="POST" action="index.php">
                            <input type="hidden" name="delete" value="<?php echo $dataItem['id']; ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    function toggleData(id) {
        var dataElement = document.getElementById('data_' + id);
        var fullDataElement = document.getElementById('fullData_' + id);
        var toggleTextElement = document.getElementById('toggleText_' + id);
        if (fullDataElement.style.display === 'none') {
            dataElement.style.display = 'none';
            fullDataElement.style.display = 'block';
            toggleTextElement.innerText = 'Show Less';
        } else {
            dataElement.style.display = 'block';
            fullDataElement.style.display = 'none';
            toggleTextElement.innerText = 'Show All';
        }
    }

    function copyToClipboard(id) {
        var dataElement = document.getElementById('fullData_' + id);
        var decodedData = tryBase64Decode(dataElement.innerText);
        var dataToCopy = decodedData !== null ? decodedData : dataElement.innerText;

        var textArea = document.createElement('textarea');
        textArea.value = dataToCopy;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);

        var copiedLabel = decodedData !== null ? 'Decoded data' : 'Data';
        alert(copiedLabel + ' copied to clipboard: ' + dataToCopy);
    }

    function tryBase64Decode(input) {
        try {
            return window.atob(input);
        } catch (e) {
            return null;
        }
    }

    // Function to periodically check for new data and show toaster popup
    function checkForNewData() {
        var latestId = <?php echo !empty($dataArray) ? $dataArray[0]['id'] : 0; ?>; // Get the latest saved data ID
        setInterval(function () {
            $.ajax({
                url: 'check_new_data.php', // Create this PHP file to check for new data
                type: 'GET',
                data: {latestId: latestId},
                success: function (response) {
                    if (response === 'true') {
                        window.location = 'index.php';
                        latestId++; // Update the latest ID
                    }
                }
            });
        }, 5000); // Check every 5 seconds
    }

    checkForNewData();
</script>

</body>
</html>
