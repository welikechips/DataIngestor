<?php
/**
 * Main Controller for DataIngestor
 *
 * This is the primary entry point for the DataIngestor application.
 * It handles data submission, retrieval, and rendering the web interface.
 *
 * @package DataIngestor
 * @author Original Developer
 * @version 1.0
 */

// Set up CORS headers to allow cross-origin requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Headers: X-Requested-With');

/**
 * Handle CORS preflight OPTIONS requests
 *
 * Browsers send an OPTIONS request before cross-origin POST requests
 * to check if the request will be allowed.
 */
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Include the access control module to validate the client's IP
require_once 'check_access.php';

/**
 * Database Setup
 *
 * Connect to the SQLite database that stores all submitted data.
 */
$db = new SQLite3('306d717c3f592af0186ed31e2f056a7d/data.db');

/**
 * Handle data deletion
 *
 * If a deletion request is made via POST with the 'delete' parameter,
 * remove the corresponding record from the database.
 */
if (isset($_POST['delete'])) {
    $deleteId = $_POST['delete'];

    // Use prepared statement to prevent SQL injection
    $stmt = $db->prepare('DELETE FROM entries WHERE id = :id');
    $stmt->bindValue(':id', $deleteId, SQLITE3_INTEGER);
    $stmt->execute();

    // Redirect back to the main page after deletion
    header('Location: index.php');
    exit();
}

/**
 * Handle data submission
 *
 * Process incoming data from POST requests and store it in the database
 * along with metadata like referrer and client IP.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['data'])) {
    $data = $_POST['data'];
    $referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
    $clientIP = getClientIP(); // Get the client's IP address

    // Insert the data and metadata into the database using prepared statements
    $stmt = $db->prepare('INSERT INTO entries (data, referrer, client_ip, entry_datetime) VALUES (:data, :referrer, :client_ip, :entry_datetime)');
    $stmt->bindValue(':data', $data, SQLITE3_TEXT);
    $stmt->bindValue(':referrer', $referrer, SQLITE3_TEXT);
    $stmt->bindValue(':client_ip', $clientIP, SQLITE3_TEXT);
    $stmt->bindValue(':entry_datetime', date('Y-m-d H:i:s'), SQLITE3_TEXT); // Add current datetime
    $stmt->execute();

    // Redirect to prevent form resubmission on page refresh
    header('Location: index.php');
    exit();
}

/**
 * Retrieve all data entries
 *
 * Fetch all records from the database, ordered by newest first.
 */
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
    <link href="/public/css/bootstrap.min.css" rel="stylesheet">
    <script src="/public/js/jquery.min.js"></script>
    <script src="/public/js/popper.min.js"></script>
    <script src="/public/js/bootstrap.min.js"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta charset="UTF-8">
    <style>
        /* Custom styles to improve usability */
        .data-container {
            word-break: break-all;
            max-height: 200px;
            overflow-y: auto;
        }
        .btn-copy {
            margin-left: 5px;
        }
        .table-responsive {
            overflow-x: auto;
        }
    </style>
</head>
<body>
<div class="container mt-4">
    <h1>Data Ingestor</h1>

    <!-- Data submission form -->
    <form method="post" class="mb-4">
        <div class="form-group">
            <label for="data">Data:</label>
            <input type="text" class="form-control" name="data" id="data" placeholder="Enter data to submit">
        </div>
        <button type="submit" class="btn btn-primary">Submit</button>
    </form>

    <h2 class="mt-4">Saved Data:</h2>
    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead class="thead-dark">
            <tr>
                <th>ID</th>
                <th style="width: 300px;">Data</th>
                <th>Referrer</th>
                <th>Client IP</th>
                <th>Date and Time</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($dataArray as $dataItem) : ?>
                <tr>
                    <td><?php echo htmlentities($dataItem['id']); ?></td>
                    <td style="width: 300px;">
                        <div id="data_<?php echo $dataItem['id']; ?>" class="data-container">
                            <?php echo htmlentities(substr($dataItem['data'], 0, 50)); ?>
                        </div>
                        <div id="fullData_<?php echo $dataItem['id']; ?>" style="display: none;" class="data-container">
                            <?php echo htmlentities($dataItem['data']); ?>
                        </div>
                        <?php if (strlen($dataItem['data']) > 50) : ?>
                            <button class="btn btn-link btn-sm" onclick="toggleData(<?php echo $dataItem['id']; ?>)">
                                <span id="toggleText_<?php echo $dataItem['id']; ?>">Show All</span>
                            </button>
                        <?php endif; ?>
                        <button class="btn btn-secondary btn-sm ml-2 btn-copy"
                                onclick="copyToClipboard(<?php echo $dataItem['id']; ?>)">Copy to Clipboard
                        </button>
                    </td>
                    <td><?php echo htmlentities($dataItem['referrer']); ?></td>
                    <td><?php echo htmlentities($dataItem['client_ip']); ?></td>
                    <td><?php echo htmlentities($dataItem['entry_datetime']); ?></td>
                    <td>
                        <form method="POST" action="index.php" onsubmit="return confirm('Are you sure you want to delete this item?');">
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
    /**
     * Toggle between showing truncated data and full data
     *
     * @param {number} id - The ID of the data entry to toggle
     */
    function toggleData(id) {
        var dataElement = document.getElementById('data_' + id);
        var fullDataElement = document.getElementById('fullData_' + id);
        var toggleTextElement = document.getElementById('toggleText_' + id);

        if (fullDataElement.style.display === 'none') {
            // Show full data
            dataElement.style.display = 'none';
            fullDataElement.style.display = 'block';
            toggleTextElement.innerText = 'Show Less';
        } else {
            // Show truncated data
            dataElement.style.display = 'block';
            fullDataElement.style.display = 'none';
            toggleTextElement.innerText = 'Show All';
        }
    }

    /**
     * Copy data to clipboard, with automatic base64 detection and decoding
     *
     * @param {number} id - The ID of the data entry to copy
     */
    function copyToClipboard(id) {
        var dataElement = document.getElementById('fullData_' + id);
        var decodedData = tryBase64Decode(dataElement.innerText);
        var dataToCopy = decodedData !== null ? decodedData : dataElement.innerText;

        // Create a temporary textarea element to perform the copy operation
        var textArea = document.createElement('textarea');
        textArea.value = dataToCopy;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);

        // Show confirmation to the user
        var copiedLabel = decodedData !== null ? 'Decoded data' : 'Data';
        alert(copiedLabel + ' copied to clipboard: ' + dataToCopy);
    }

    /**
     * Attempt to decode a string as base64
     *
     * @param {string} input - The string to try decoding
     * @return {string|null} The decoded string if successful, null otherwise
     */
    function tryBase64Decode(input) {
        try {
            return window.atob(input);
        } catch (e) {
            return null;
        }
    }

    /**
     * Periodically check for new data and refresh the page when new entries are found
     *
     * This function sets up a polling mechanism to check for new data entries
     * every 5 seconds and automatically refreshes the page when new data is available.
     */
    function checkForNewData() {
        var latestId = <?php echo !empty($dataArray) ? $dataArray[0]['id'] : 0; ?>; // Get the latest saved data ID

        setInterval(function () {
            $.ajax({
                url: 'check_new_data.php',
                type: 'GET',
                data: {latestId: latestId},
                success: function (response) {
                    if (response === 'true') {
                        window.location = 'index.php'; // Reload the page to show new data
                        latestId++; // Update the latest ID
                    }
                }
            });
        }, 5000); // Check every 5 seconds
    }

    // Initialize the new data checker when the page loads
    checkForNewData();
</script>

</body>
</html>